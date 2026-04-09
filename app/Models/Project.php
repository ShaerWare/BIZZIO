<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model
{
    use HasFactory, LogsActivity, Searchable, SoftDeletes;

    // ❌ ВРЕМЕННО УБИРАЕМ Spatie Media Library
    // use InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'full_description',
        'avatar',
        'start_date',
        'end_date',
        'is_ongoing',
        'status',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_ongoing' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Автоматическая генерация slug при создании
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    // Связи

    /**
     * Компания-заказчик (владелец проекта)
     */
    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /**
     * Создатель проекта
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Компании-участники проекта (многие-ко-многим через pivot)
     */
    public function participants()
    {
        return $this->belongsToMany(Company::class, 'company_project')
            ->withPivot([
                'role',
                'participation_description',
                'customer_review',
                'customer_rating',
                'participant_review',
                'participant_rating',
            ])
            ->withTimestamps();
    }

    /**
     * Пользователи-участники проекта (многие-ко-многим через project_user)
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot(['company_id', 'role', 'added_by', 'added_at'])
            ->withTimestamps();
    }

    /**
     * Запросы на присоединение к проекту
     */
    public function joinRequests()
    {
        return $this->hasMany(ProjectJoinRequest::class);
    }

    /**
     * Комментарии к проекту
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * Все комментарии (включая вложенные)
     */
    public function allComments()
    {
        return $this->hasMany(Comment::class);
    }

    // Методы

    /**
     * Проверка, является ли пользователь модератором компании-заказчика
     */
    public function canManage(User $user): bool
    {
        return $this->company->isModerator($user) || $user->inRole('admin');
    }

    /**
     * Проверка, является ли пользователь участником проекта
     */
    public function isMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Проверка, есть ли у пользователя ожидающий запрос
     */
    public function hasPendingRequestFrom(User $user): bool
    {
        return $this->joinRequests()->where('user_id', $user->id)->pending()->exists();
    }

    /**
     * Добавление пользователя как участника проекта
     */
    public function addMember(User $user, Company $company, string $role = 'member', ?User $addedBy = null): void
    {
        $this->members()->attach($user->id, [
            'company_id' => $company->id,
            'role' => $role,
            'added_by' => $addedBy?->id,
            'added_at' => now(),
        ]);
    }

    /**
     * Удаление участника проекта
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Получить роль пользователя в проекте
     */
    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('users.id', $user->id)->first();

        return $member?->pivot->role;
    }

    /**
     * Проверка, может ли пользователь управлять ролью конкретного участника проекта.
     * Администратор проекта — в рамках всего проекта.
     * Модератор проекта — только участники своей компании.
     */
    public function canManageMember(User $actor, User $target): bool
    {
        // Системный администратор (Orchid)
        if ($actor->inRole('admin')) {
            return true;
        }

        // Модератор/владелец компании-заказчика проекта = полный доступ (как admin проекта)
        if ($this->company->isModerator($actor)) {
            return true;
        }

        $actorRole = $this->getMemberRole($actor);

        // Администратор проекта = полный доступ
        if ($actorRole === 'admin') {
            return true;
        }

        // Модератор проекта = только участники своей компании
        if ($actorRole === 'moderator') {
            $actorCompanyId = $this->members()->where('users.id', $actor->id)->first()?->pivot->company_id;
            $targetCompanyId = $this->members()->where('users.id', $target->id)->first()?->pivot->company_id;

            return $actorCompanyId && $actorCompanyId === $targetCompanyId;
        }

        return false;
    }

    /**
     * Получить список ролей, которые actor может назначать.
     * Администратор проекта — все роли.
     * Модератор проекта — только модератор и участник.
     */
    public function getAssignableRoles(User $actor): array
    {
        $allRoles = self::getUserRoles();

        // Системный администратор или модератор/владелец компании-заказчика
        if ($actor->inRole('admin') || $this->company->isModerator($actor)) {
            return $allRoles;
        }

        $actorRole = $this->getMemberRole($actor);

        if ($actorRole === 'admin') {
            return $allRoles;
        }

        if ($actorRole === 'moderator') {
            unset($allRoles['admin']);

            return $allRoles;
        }

        return [];
    }

    /**
     * Проверка, может ли пользователь добавлять участников в проект.
     * canManage() (админы/владельцы) + модераторы проекта (только из своей компании).
     */
    public function canAddMember(User $user): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $this->getMemberRole($user) === 'moderator';
    }

    /**
     * Получение ролей пользователей в проекте
     */
    public static function getUserRoles(): array
    {
        return [
            'admin' => 'Администратор',
            'moderator' => 'Модератор',
            'member' => 'Участник',
        ];
    }

    /**
     * Добавление компании-участника
     */
    public function addParticipant(Company $company, string $role, ?string $description = null): void
    {
        $this->participants()->attach($company->id, [
            'role' => $role,
            'participation_description' => $description,
        ]);
    }

    /**
     * Удаление участника
     */
    public function removeParticipant(Company $company): void
    {
        $this->participants()->detach($company->id);
    }

    /**
     * Получение списка доступных ролей участников
     */
    public static function getParticipantRoles(): array
    {
        return [
            'customer' => 'Заказчик',
            'general_contractor' => 'Генподрядчик',
            'contractor' => 'Подрядчик',
            'supplier' => 'Поставщик',
            'consultant' => 'Консультант',
        ];
    }

    /**
     * Получение списка статусов проекта
     */
    public static function getStatuses(): array
    {
        return [
            'active' => 'Активный',
            'completed' => 'Завершённый',
            'cancelled' => 'Отменённый',
        ];
    }

    // Accessors

    /**
     * URL аватара проекта
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = $this->avatar;

        if (! $avatar || ! is_string($avatar) || str_starts_with($avatar, '[')) {
            return null;
        }

        $avatar = ltrim($avatar, '/');
        if (str_starts_with($avatar, 'storage/')) {
            return asset($avatar);
        }

        return asset('storage/'.$avatar);
    }

    /**
     * Форматированные сроки проекта
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->start_date) {
            return 'Сроки не указаны';
        }

        if ($this->is_ongoing) {
            return $this->start_date->format('d.m.Y').' — По настоящее время';
        }

        if ($this->end_date) {
            return $this->start_date->format('d.m.Y').' — '.$this->end_date->format('d.m.Y');
        }

        return $this->start_date->format('d.m.Y');
    }

    // Scopes

    /**
     * Только активные проекты
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Только завершённые проекты
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Поиск по названию
     */
    public function scopeSearch($query, $search)
    {
        $op = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where('name', $op, '%'.$search.'%');
    }

    public function getContent(): string
    {
        return $this->name.($this->company ? ' — '.$this->company->name : '');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Настройки логирования активности
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'start_date', 'end_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'создал(а) проект',
                'updated' => 'обновил(а) проект',
                'deleted' => 'удалил(а) проект',
                default => $eventName,
            });
    }

    // ========================
    // ПОИСК (SCOUT)
    // ========================

    /**
     * Поля для индексации поиска
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'full_description' => $this->full_description,
        ];
    }
}
