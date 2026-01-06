<?php

namespace App\Jobs;

use App\Models\Auction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateAuctionStatuses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 public function handle(): void
{
    $now = Carbon::now();
    
    Log::info('=== UpdateAuctionStatuses: СТАРТ ===', ['time' => $now->toDateTimeString()]);
    
    // 1. Активные аукционы, у которых истёк срок приёма заявок
    $expiredActive = Auction::where('status', 'active')
        ->where('end_date', '<=', $now)
        ->where('trading_start', '<=', $now)
        ->get();
    
    Log::info('Найдено истёкших активных аукционов: ' . $expiredActive->count());
    
    foreach ($expiredActive as $auction) {
        $bidsCount = $auction->initialBids()->count();
        
        Log::info("Проверка аукциона {$auction->number}", [
            'status' => $auction->status,
            'end_date' => $auction->end_date,
            'trading_start' => $auction->trading_start,
            'bids_count' => $bidsCount,
        ]);
        
        if ($bidsCount > 0) {
            $auction->update(['status' => 'trading']);
            
            // Генерируем анонимные коды
            foreach ($auction->initialBids as $bid) {
                if (!$bid->anonymous_code) {
                    $code = Auction::generateAnonymousCode();
                    $bid->update(['anonymous_code' => $code]);
                    Log::info("Сгенерирован код {$code} для заявки {$bid->id}");
                }
            }
            
            Log::info("✅ Аукцион {$auction->number} переведён в 'trading'");
        } else {
            $auction->update(['status' => 'cancelled']);
            Log::warning("❌ Аукцион {$auction->number} отменён (нет заявок)");
        }
    }
    
    // 2. Торги, у которых прошло 20 минут с последней ставки
    $expiredTrading = Auction::where('status', 'trading')
        ->whereNotNull('last_bid_at')
        ->where('last_bid_at', '<=', $now->copy()->subMinutes(20))
        ->get();
    
    Log::info('Найдено завершённых торгов: ' . $expiredTrading->count());
    
    foreach ($expiredTrading as $auction) {
        $winnerBid = $auction->tradingBids()
            ->orderBy('price', 'asc')
            ->first();
        
        if ($winnerBid) {
            $winnerBid->update(['status' => 'winner']);
            $auction->update([
                'status' => 'closed',
                'winner_company_id' => $winnerBid->company_id,
            ]);
            
            Log::info("✅ Аукцион {$auction->number} завершён. Победитель: компания ID {$winnerBid->company_id}");
        }
    }
    
    // 3. Торги без ставок 24 часа
    $tradingWithoutBids = Auction::where('status', 'trading')
        ->whereNull('last_bid_at')
        ->where('trading_start', '<=', $now->copy()->subHours(24))
        ->get();
    
    Log::info('Найдено торгов без ставок: ' . $tradingWithoutBids->count());
    
    foreach ($tradingWithoutBids as $auction) {
        $auction->update(['status' => 'cancelled']);
        Log::warning("❌ Аукцион {$auction->number} отменён (нет ставок 24 часа)");
    }
    
    Log::info('=== UpdateAuctionStatuses: ЗАВЕРШЕНО ===');
}
}