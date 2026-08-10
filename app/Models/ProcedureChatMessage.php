<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * #218 Сообщение чата процедуры (вопрос участника / ответ организатора / системная запись).
 */
class ProcedureChatMessage extends Model
{
    protected $fillable = [
        'procedure_type',
        'procedure_id',
        'user_id',
        'company_id',
        'is_organizer',
        'is_system',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'is_organizer' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function procedure(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
