<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

/**
 * Бейдж (ачивка) пользователя: цветная рамка + подпись.
 * Назначается администратором из Orchid; у пользователя может быть несколько бейджей.
 */
class UserBadge extends Model
{
    use AsSource;

    /**
     * Пресеты цвета рамки (значение — итоговый HEX).
     */
    public const COLOR_PRESETS = [
        'red' => '#dc3545',
        'bordeaux' => '#7b1e1e',
        'green' => '#28a745',
    ];

    /**
     * Пресеты подписи ('none' — рамка без подписи).
     */
    public const LABEL_PRESETS = [
        'suspicious' => 'Подозрительная личность',
        'none' => '',
        'confirmed' => 'Подтверждён',
    ];

    protected $fillable = [
        'user_id',
        'color',
        'label',
        'created_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Разрешает выбранный пресет/кастом в итоговый HEX цвета рамки.
     */
    public static function resolveColor(string $preset, ?string $custom): string
    {
        if ($preset === 'custom') {
            return $custom !== null && $custom !== '' ? $custom : self::COLOR_PRESETS['green'];
        }

        return self::COLOR_PRESETS[$preset] ?? self::COLOR_PRESETS['green'];
    }

    /**
     * Разрешает выбранный пресет/кастом в итоговую подпись бейджа.
     */
    public static function resolveLabel(string $preset, ?string $custom): string
    {
        if ($preset === 'custom') {
            return trim((string) $custom);
        }

        return self::LABEL_PRESETS[$preset] ?? '';
    }
}
