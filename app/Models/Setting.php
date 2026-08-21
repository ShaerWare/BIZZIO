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

    /** #296 Ключ: срок хранения конкурсной документации (в днях). */
    public const DOCUMENTS_RETENTION_DAYS = 'documents_retention_days';

    /** #296 Значение по умолчанию срока хранения — 30 дней после завершения процедуры. */
    public const DEFAULT_DOCUMENTS_RETENTION_DAYS = 30;

    /** Прежний ключ (месяцы) — читается как запасной вариант для старых установок. */
    public const DOCUMENTS_RETENTION_MONTHS = 'documents_retention_months';

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

    /**
     * #296 Срок хранения конкурсной документации в днях (≥ 1).
     *
     * Если настройка ещё в старом формате (месяцы), пересчитываем её в дни — иначе после
     * выката срок молча схлопнулся бы с «3 месяца» до «3 дня».
     */
    public static function documentsRetentionDays(): int
    {
        $days = static::get(self::DOCUMENTS_RETENTION_DAYS);

        if ($days !== null) {
            return max(1, (int) $days);
        }

        $months = static::get(self::DOCUMENTS_RETENTION_MONTHS);

        if ($months !== null) {
            return max(1, (int) $months * 30);
        }

        return self::DEFAULT_DOCUMENTS_RETENTION_DAYS;
    }
}
