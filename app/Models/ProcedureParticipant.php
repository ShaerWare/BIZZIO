<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * #218 Участник процедуры: обезличенный код в чате и состояние отстранения.
 *
 * @property string $chat_code
 */
class ProcedureParticipant extends Model
{
    protected $fillable = [
        'procedure_type',
        'procedure_id',
        'company_id',
        'chat_code',
        'banned_at',
        'ban_reason',
        'banned_by',
    ];

    protected function casts(): array
    {
        return [
            'banned_at' => 'datetime',
        ];
    }

    public function procedure(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }
}
