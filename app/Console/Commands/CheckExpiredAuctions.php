<?php

namespace App\Console\Commands;

use App\Jobs\CloseAuctionJob;
use App\Models\Auction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiredAuctions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'auctions:check-expired';

    /**
     * The console command description.
     */
    protected $description = 'Проверяет истёкшие аукционы и планирует их закрытие';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Проверка истёкших аукционов...');

        // Находим аукционы в торгах, у которых прошло 20+ минут с последней ставки
        $expiredAuctions = Auction::where('status', 'trading')
            ->where('last_bid_at', '<=', Carbon::now()->subMinutes(20))
            ->get();

        if ($expiredAuctions->isEmpty()) {
            $this->info('Истёкших аукционов не найдено.');

            return 0;
        }

        $this->info("Найдено истёкших аукционов: {$expiredAuctions->count()}");

        foreach ($expiredAuctions as $auction) {
            $this->line("Планируем закрытие аукциона: {$auction->number}");

            // Планируем Job на закрытие
            CloseAuctionJob::dispatch($auction->id);
        }

        $this->info('Задачи на закрытие аукционов добавлены в очередь.');

        return 0;
    }
}
