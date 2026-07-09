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
        'deadline',
        'advance_percent',
        'anonymous_code',
        'comment',
        'type',
        'status',
        'score_price',
        'score_deadline',
        'score_advance',
        'total_score',
        'is_base',
        'became_best_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'advance_percent' => 'decimal:2',
        'score_price' => 'decimal:4',
        'score_deadline' => 'decimal:4',
        'score_advance' => 'decimal:4',
        'total_score' => 'decimal:4',
        'is_base' => 'boolean',
        'became_best_at' => 'datetime',
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
        return $this->belongsTo(Company::class)->withTrashed();
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

    /**
     * #179 Проверка: это коммерческое предложение (этап 2)
     */
    public function isOffer(): bool
    {
        return $this->type === 'offer';
    }
}
