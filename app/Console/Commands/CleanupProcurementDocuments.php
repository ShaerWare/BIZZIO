<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\Rfq;
use App\Models\Setting;
use App\Support\ProcurementDocuments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * #185 Автоудаление конкурсной документации завершённых процедур старше срока хранения.
 *
 * #296 Срок задаётся в админке в днях (Setting: documents_retention_days, по умолчанию 30)
 * и отсчитывается от `closed_at` — момента завершения процедуры. Раньше отсчёт шёл от
 * `updated_at`, который сдвигало любое изменение процедуры, и документы жили дольше срока.
 * Протоколы не удаляются — только документация (Извещение / ТЗ / Проект договора / Прочие).
 */
class CleanupProcurementDocuments extends Command
{
    protected $signature = 'documents:cleanup';

    protected $description = 'Удаляет конкурсную документацию завершённых процедур старше срока хранения (#185)';

    public function handle(): int
    {
        $days = Setting::documentsRetentionDays();
        $cutoff = now()->subDays($days);
        $collections = array_keys(ProcurementDocuments::COLLECTIONS);
        $deletedFiles = 0;
        $affected = 0;

        foreach ([Rfq::class, Auction::class] as $modelClass) {
            $modelClass::query()
                ->whereIn('status', ['closed', 'cancelled'])
                // Черновой отбор по дате; окончательное решение принимает
                // documentsRetentionExpired() ниже (учитывает этап 2 у коммерческих процедур).
                // closed_at заполнен у всех завершённых процедур (миграция #296),
                // updated_at остаётся запасным вариантом на случай ручных правок в БД.
                ->where(fn ($query) => $query
                    ->where('closed_at', '<', $cutoff)
                    ->orWhere(fn ($fallback) => $fallback
                        ->whereNull('closed_at')
                        ->where('updated_at', '<', $cutoff)))
                ->each(function ($procedure) use ($collections, &$deletedFiles, &$affected) {
                    // #296 У двухэтапной процедуры срок хранения отсчитывается от завершения
                    // этапа 2: пока аукцион идёт (или закрыт недавно), документацию этапа 1 не трогаем.
                    if (! $procedure->documentsRetentionExpired()) {
                        return;
                    }

                    $removedHere = 0;

                    foreach ($collections as $collection) {
                        $count = $procedure->getMedia($collection)->count();
                        if ($count > 0) {
                            $procedure->clearMediaCollection($collection);
                            $removedHere += $count;
                        }
                    }

                    if ($removedHere > 0) {
                        $deletedFiles += $removedHere;
                        $affected++;
                    }
                });
        }

        $message = "Автоудаление документации: удалено {$deletedFiles} файлов у {$affected} процедур (срок хранения {$days} дн.).";
        $this->info($message);
        Log::info('[documents:cleanup] '.$message);

        return self::SUCCESS;
    }
}
