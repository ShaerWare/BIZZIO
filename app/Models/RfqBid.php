<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class RfqBid extends Model
{
    use HasFactory, SoftDeletes;
    use AsSource, Filterable;

    protected $fillable = [
        'rfq_id',
        'company_id',
        'user_id',
        'price',
        'deadline',
        'advance_percent',
        'comment',
        'score_price',
        'score_deadline',
        'score_advance',
        'total_score',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'deadline' => 'integer',
        'advance_percent' => 'decimal:2',
        'score_price' => 'decimal:4',
        'score_deadline' => 'decimal:4',
        'score_advance' => 'decimal:4',
        'total_score' => 'decimal:4',
    ];

    // ========================
    // СВЯЗИ (RELATIONSHIPS)
    // ========================

    /**
     * RFQ, к которому относится заявка
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /**
     * Компания-участник
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Пользователь, подавший заявку
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================
    // МЕТОДЫ
    // ========================

    /**
     * Проверка: может ли пользователь редактировать заявку
     */
    public function canManage(User $user): bool
    {
        // Автор заявки или модератор компании-участника
        return $this->user_id === $user->id 
            || $this->company->isModerator($user)
            || $user->hasAccess('platform.systems.rfqs');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Только принятые заявки
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Заявка-победитель
     */
    public function scopeWinner($query)
    {
        return $query->where('status', 'winner');
    }
}