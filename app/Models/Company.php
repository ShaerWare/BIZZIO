<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Orchid\Filters\Filterable; 
use Orchid\Screen\AsSource;

class Company extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;
    use AsSource, Filterable;

    protected $fillable = [
        'name',
        'slug',
        'inn',
        'legal_form',
        'logo',
        'short_description',
        'full_description',
        'industry_id',
        'is_verified',
        'created_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Boot method для автогенерации slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
                
                // Проверка уникальности slug
                $originalSlug = $company->slug;
                $counter = 1;
                
                while (static::where('slug', $company->slug)->exists()) {
                    $company->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($company) {
            // Если название изменилось, обновляем slug
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
                
                $originalSlug = $company->slug;
                $counter = 1;
                
                while (static::where('slug', $company->slug)->where('id', '!=', $company->id)->exists()) {
                    $company->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * Route Model Binding по slug
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    /**
     * Отрасль
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Создатель компании
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Модераторы компании (многие ко многим)
     */
    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot(['role', 'added_by', 'added_at', 'can_manage_moderators'])
            ->withTimestamps();
    }

    /**
     * Запросы на присоединение к компании
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(CompanyJoinRequest::class);
    }

    /**
     * Проекты компании (как заказчик)
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'company_id');
    }

    /**
     * Проекты, где компания участвует
     */
    public function participatedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'company_project')
            ->withPivot(['role', 'participation_description'])
            ->withTimestamps();
    }

    /**
     * Запросы котировок (RFQ) компании
     */
    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class);
    }

    /**
     * Заявки компании на RFQ
     */
    public function rfqBids(): HasMany
    {
        return $this->hasMany(RfqBid::class);
    }

    /**
     * Проверка: является ли пользователь модератором компании
     */
    public function isModerator(User $user): bool
    {
        return $this->moderators()->where('user_id', $user->id)->exists();
    }

    /**
     * Проверка: может ли пользователь управлять модераторами
     */
    public function canManageModerators(User $user): bool
    {
        // Админы могут всё
        if ($user->hasAccess('platform.systems.users')) {
            return true;
        }

        // Создатель может всегда
        if ($this->created_by === $user->id) {
            return true;
        }

        // Модераторы с правом управления
        $moderator = $this->moderators()->where('user_id', $user->id)->first();
        return $moderator && $moderator->pivot->can_manage_moderators;
    }

    /**
     * Проверка: есть ли активный запрос от пользователя
     */
    public function hasPendingRequestFrom(User $user): bool
    {
        return $this->joinRequests()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Назначить модератора компании
     */
    public function assignModerator(User $user, string $role = null, User $addedBy = null, bool $canManageModerators = false): void
    {
        // Проверяем, что пользователь ещё не модератор
        if ($this->isModerator($user)) {
            return;
        }

        $this->moderators()->attach($user->id, [
            'role' => $role ?? 'moderator',
            'added_by' => $addedBy?->id ?? auth()->id(),
            'added_at' => now(),
            'can_manage_moderators' => $canManageModerators,
        ]);
    }

    /**
     * Удалить модератора из компании
     */
    public function removeModerator(User $user): void
    {
        $this->moderators()->detach($user->id);
    }

    /**
     * Media Collections (для документов: Устав, ИНН, ОГРН)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
         ->singleFile() // только один файл
         ->useDisk('public')
         ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
         
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }

    /**
     * Scope: Только верифицированные компании
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: Поиск по названию или ИНН
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('inn', 'like', "%{$search}%");
        });
    }

    /**
     * Accessor: URL логотипа
     */
    public function getLogoUrlAttribute(): string
    {
        return $this->logo 
            ? asset('storage/' . $this->logo)
            : asset('images/default-company-logo.png');
    }
}