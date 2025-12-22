<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyJoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'desired_role',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Компания
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Пользователь, подавший запрос
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Пользователь, рассмотревший запрос
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Проверка: может ли пользователь отозвать запрос
     */
    public function canCancel(User $user): bool
    {
        return $this->user_id === $user->id && $this->status === 'pending';
    }

    /**
     * Проверка: может ли пользователь рассмотреть запрос
     */
    public function canReview(User $user): bool
    {
        return $this->company->canManageModerators($user) && $this->status === 'pending';
    }
}