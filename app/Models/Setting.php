<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

/**
 * #185 Настройки приложения (ключ-значение), редактируемые в админке.
 */
class Setting extends Model
{
    use AsSource;

    /** Ключ: срок хранения конкурсной документации (в месяцах). */
    public const DOCUMENTS_RETENTION_MONTHS = 'documents_retention_months';

    /** Значение по умолчанию срока хранения (месяцы). */
    public const DEFAULT_DOCUMENTS_RETENTION_MONTHS = 3;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /** Срок хранения конкурсной документации (месяцы, ≥ 1). */
    public static function documentsRetentionMonths(): int
    {
        return max(1, (int) static::get(
            self::DOCUMENTS_RETENTION_MONTHS,
            self::DEFAULT_DOCUMENTS_RETENTION_MONTHS
        ));
    }
}
