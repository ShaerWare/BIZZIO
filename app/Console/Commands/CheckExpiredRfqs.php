<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CloseRfqJob;
use App\Models\Rfq;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiredRfqs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rfqs:check-expired';

    /**
     * The console command description.
     */
    protected $description = 'Закрывает запросы цен, у которых истёк срок приёма заявок';

    /**
     * Execute the console command.
     *
     * Подстраховка к отложенной CloseRfqJob->delay(end_date): если воркер был
     * перезапущен/недоступен в момент end_date, отложенная задача теряется и RFQ
     * навсегда зависает в статусе "active". Эта команда (по аналогии с аукционами)
     * каждую минуту находит просроченные активные RFQ и ставит их на закрытие.
     */
    public function handle(): int
    {
        $this->info('Проверка истёкших запросов цен...');

        $expiredRfqs = Rfq::where('status', 'active')
            ->where('end_date', '<=', Carbon::now())
            ->get();

        if ($expiredRfqs->isEmpty()) {
            $this->info('Истёкших запросов цен не найдено.');

            return self::SUCCESS;
        }

        $this->info("Найдено истёкших запросов цен: {$expiredRfqs->count()}");

        foreach ($expiredRfqs as $rfq) {
            $this->line("Планируем закрытие запроса цен: {$rfq->number}");

            CloseRfqJob::dispatch($rfq);
        }

        $this->info('Задачи на закрытие запросов цен добавлены в очередь.');

        return self::SUCCESS;
    }
}
