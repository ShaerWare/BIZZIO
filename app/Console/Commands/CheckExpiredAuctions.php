<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Jobs\CloseAuctionJob;
use Illuminate\Console\Command;
use Carbon\Carbon;

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