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
 * Срок хранения задаётся в админке (Setting: documents_retention_months, по умолчанию 3 мес.).
 * Протоколы не удаляются — только документация (Извещение / ТЗ / Проект договора / Прочие).
 */
class CleanupProcurementDocuments extends Command
{
    protected $signature = 'documents:cleanup';

    protected $description = 'Удаляет конкурсную документацию завершённых процедур старше срока хранения (#185)';

    public function handle(): int
    {
        $months = Setting::documentsRetentionMonths();
        $cutoff = now()->subMonths($months);
        $collections = array_keys(ProcurementDocuments::COLLECTIONS);
        $deletedFiles = 0;
        $affected = 0;

        foreach ([Rfq::class, Auction::class] as $modelClass) {
            $modelClass::query()
                ->whereIn('status', ['closed', 'cancelled'])
                ->where('updated_at', '<', $cutoff)
                ->each(function ($procedure) use ($collections, &$deletedFiles, &$affected) {
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

        $message = "Автоудаление документации: удалено {$deletedFiles} файлов у {$affected} процедур (срок хранения {$months} мес.).";
        $this->info($message);
        Log::info('[documents:cleanup] '.$message);

        return self::SUCCESS;
    }
}
