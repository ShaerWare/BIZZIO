<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuctionBid extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'auction_id',
        'company_id',
        'user_id',
        'price',
        'anonymous_code',
        'comment',
        'type',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // ========================
    // СВЯЗИ (RELATIONSHIPS)
    // ========================

    /**
     * Аукцион
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * Компания-участник
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Пользователь, подавший ставку
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================
    // МЕТОДЫ
    // ========================

    /**
     * Проверка: может ли пользователь управлять этой ставкой
     */
    public function canManage(User $user): bool
    {
        // Автор ставки или модератор компании или админ
        return $this->user_id === $user->id 
            || $this->company->isModerator($user)
            || $user->hasAccess('platform.systems.auctions');
    }

    /**
     * Проверка: это заявка на участие или ставка в торгах
     */
    public function isInitialBid(): bool
    {
        return $this->type === 'initial';
    }

    /**
     * Проверка: это ставка в торгах
     */
    public function isTradingBid(): bool
    {
        return $this->type === 'bid';
    }
}