<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Searchable;

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
        return $this->belongsTo(Company::class);
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
                'participant_rating'
            ])
            ->withTimestamps();
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
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /**
     * Форматированные сроки проекта
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->is_ongoing) {
            return $this->start_date->format('d.m.Y') . ' — По настоящее время';
        }

        if ($this->end_date) {
            return $this->start_date->format('d.m.Y') . ' — ' . $this->end_date->format('d.m.Y');
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
        return $query->where('name', 'like', '%' . $search . '%');
    }

    public function getContent(): string
    {
        return $this->name . ($this->company ? ' — ' . $this->company->name : '');
    }

    public function getRouteKeyName()
    {
        return 'id'; // по умолчанию и так id, но на случай переопределения
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
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
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