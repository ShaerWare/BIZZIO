<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class RfqInvitation extends Model
{
    use HasFactory;
    use AsSource, Filterable;

    protected $fillable = [
        'rfq_id',
        'company_id',
        'invited_by',
        'status',
    ];

    // ========================
    // СВЯЗИ (RELATIONSHIPS)
    // ========================

    /**
     * RFQ, к которому относится приглашение
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    /**
     * Приглашённая компания
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Пользователь, отправивший приглашение
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Только ожидающие ответа приглашения
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}