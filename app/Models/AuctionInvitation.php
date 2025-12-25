<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'company_id',
        'status',
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
     * Приглашённая компания
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}