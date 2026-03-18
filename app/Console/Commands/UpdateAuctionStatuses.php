<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auction;
use App\Jobs\CloseAuctionJob;
use Carbon\Carbon;

class UpdateAuctionStatuses extends Command
{
    protected $signature = 'auctions:update-statuses';
    protected $description = 'Обновить статусы аукционов на основе текущего времени';

    public function handle(): int
    {
        $this->info('🔄 Начинаю обновление статусов аукционов...');
        
        $now = Carbon::now();
        $this->line("Текущее время: {$now->toDateTimeString()}");
        
        // 1. Активные аукционы → Торги
        $expiredActive = Auction::where('status', 'active')
            ->where('end_date', '<=', $now)
            ->where('trading_start', '<=', $now)
            ->get();
        
        $this->line("\n📋 Найдено истёкших активных аукционов: {$expiredActive->count()}");
        
        foreach ($expiredActive as $auction) {
            $bidsCount = $auction->initialBids()->count();
            
            $this->line("  • {$auction->number}: заявок={$bidsCount}");
            
            if ($bidsCount > 0) {
                $auction->update(['status' => 'trading']);
                
                // Генерируем анонимные коды
                foreach ($auction->initialBids as $bid) {
                    if (!$bid->anonymous_code) {
                        $code = Auction::generateAnonymousCode();
                        $bid->update(['anonymous_code' => $code]);
                    }
                }
                
                $this->info("    ✅ Переведён в 'trading'");
            } else {
                $auction->update(['status' => 'cancelled']);
                $this->warn("    ❌ Отменён (нет заявок)");
            }
        }
        
        // 2. Торги → Завершён
        $expiredTrading = Auction::where('status', 'trading')
            ->whereNotNull('last_bid_at')
            ->where('last_bid_at', '<=', $now->copy()->subMinutes(20))
            ->get();
        
        $this->line("\n🏁 Найдено завершённых торгов: {$expiredTrading->count()}");
        
        foreach ($expiredTrading as $auction) {
            CloseAuctionJob::dispatch($auction->id);
            $this->info("  📋 {$auction->number} — запланировано закрытие");
        }
        
        // 3. Торги без ставок → Отменён
        $tradingWithoutBids = Auction::where('status', 'trading')
            ->whereNull('last_bid_at')
            ->where('trading_start', '<=', $now->copy()->subHours(24))
            ->get();
        
        $this->line("\n⏰ Торгов без ставок 24ч: {$tradingWithoutBids->count()}");
        
        foreach ($tradingWithoutBids as $auction) {
            $auction->update(['status' => 'cancelled']);
            $this->warn("  ❌ {$auction->number} отменён (нет ставок)");
        }
        
        $this->info("\n✅ Обновление завершено!");
        
        return 0;
    }
}