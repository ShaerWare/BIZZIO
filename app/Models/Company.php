<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
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
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Проверка: является ли пользователь модератором компании
     */
    public function isModerator(User $user): bool
    {
        return $this->moderators()->where('user_id', $user->id)->exists();
    }

    /**
     * Назначение модератора
     */
    public function assignModerator(User $user, string $role = 'moderator'): void
    {
        if (!$this->isModerator($user)) {
            $this->moderators()->attach($user->id, ['role' => $role]);
        }
    }

    /**
     * Снятие модератора
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
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }
}