<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * #296 Фиксирует момент завершения процедуры в `closed_at`.
 *
 * Статус на `closed`/`cancelled` переводится из девяти разных мест (джобы, сервисы, отмена
 * организатором), поэтому отметка ставится модельным событием, а не в каждом из них.
 */
trait TracksClosedAt
{
    public static function bootTracksClosedAt(): void
    {
        static::saving(function (self $model): void {
            if (! $model->isDirty('status')) {
                return;
            }

            if (in_array($model->status, ['closed', 'cancelled'], true)) {
                // Повторное закрытие не сдвигает дату — срок хранения считается от первого.
                $model->closed_at ??= now();

                return;
            }

            // Возврат в работу (например, из отменённого в черновик) снимает отметку.
            $model->closed_at = null;
        });
    }
}
