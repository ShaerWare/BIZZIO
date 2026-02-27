<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Scout\Searchable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\User as Orchid;

class User extends Orchid
{
    use HasFactory, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'phone',
        'position',
        'bio',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions' => 'array',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'name' => Like::class,
        'email' => Like::class,
        'updated_at' => WhereDateStartEnd::class,
        'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    // ========================
    // СВЯЗИ (RELATIONSHIPS)
    // ========================

    /**
     * Компании, где пользователь является создателем
     */
    public function createdCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'created_by');
    }

    /**
     * Компании, где пользователь является модератором (через pivot-таблицу)
     */
    public function moderatedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->select('companies.id', 'companies.name', 'companies.slug', 'companies.inn', 'companies.is_verified') // ⚠️ ЯВНО указываем поля
            ->withPivot(['role', 'added_by', 'added_at', 'can_manage_moderators'])
            ->withTimestamps();
    }

    /**
     * Проекты, созданные пользователем
     */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Комментарии пользователя
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Проекты, где пользователь является участником (через pivot-таблицу)
     */
    public function projectMemberships(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withPivot(['company_id', 'role', 'added_by', 'added_at'])
            ->withTimestamps();
    }

    /**
     * Запросы на присоединение к компаниям
     */
    public function companyJoinRequests(): HasMany
    {
        return $this->hasMany(CompanyJoinRequest::class);
    }

    /**
     * Активные запросы на присоединение (pending)
     */
    public function pendingCompanyRequests(): HasMany
    {
        return $this->hasMany(CompanyJoinRequest::class)->where('status', 'pending');
    }

    /**
     * Подписки пользователя (на кого подписан)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'subscriber_id');
    }

    /**
     * Подписчики пользователя (кто подписан на меня)
     */
    public function subscribers(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    // ========================
    // МЕТОДЫ
    // ========================

    /**
     * Проверка, является ли пользователь модератором хотя бы одной компании
     */
    public function isModeratorOfAnyCompany(): bool
    {
        return $this->moderatedCompanies()->exists();
    }

    /**
     * Проверка, является ли пользователь модератором конкретной компании
     */
    public function isModeratorOf(Company $company): bool
    {
        return $this->moderatedCompanies()->where('companies.id', $company->id)->exists();
    }

    /**
     * Проверка: подписан ли пользователь на цель
     */
    public function isSubscribedTo(Model $target): bool
    {
        return $this->subscriptions()
            ->where('subscribable_type', $target->getMorphClass())
            ->where('subscribable_id', $target->getKey())
            ->exists();
    }

    /**
     * Связь: У пользователя много ключевых слов
     */
    public function keywords()
    {
        return $this->hasMany(UserKeyword::class);
    }

    // ========================
    // АВАТАР
    // ========================

    /**
     * URL аватара пользователя
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            // Если это внешний URL (например, от OAuth)
            if (str_starts_with($this->avatar, 'http')) {
                return $this->avatar;
            }

            // Если это локальный файл
            return asset('storage/'.$this->avatar);
        }

        // Дефолтный аватар (генерация по инициалам)
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
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
            'email' => $this->email,
            'position' => $this->position,
            'bio' => $this->bio,
        ];
    }
}
