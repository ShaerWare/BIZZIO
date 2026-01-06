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
        
        Log::info('UpdateAuctionStatuses: Начало проверки статусов аукционов');
        
        // 1. Активные аукционы, у которых истёк срок приёма заявок → переводим в "trading"
        $expiredActive = Auction::where('status', 'active')
            ->where('end_date', '<=', $now)
            ->where('trading_start', '<=', $now)
            ->get();
        
        foreach ($expiredActive as $auction) {
            // Проверяем, есть ли заявки
            if ($auction->initialBids()->count() > 0) {
                $auction->update(['status' => 'trading']);
                
                // Генерируем анонимные коды для всех участников
                foreach ($auction->initialBids as $bid) {
                    if (!$bid->anonymous_code) {
                        $bid->update([
                            'anonymous_code' => Auction::generateAnonymousCode()
                        ]);
                    }
                }
                
                Log::info("Аукцион {$auction->number} переведён в статус 'trading'");
            } else {
                // Нет заявок — отменяем аукцион
                $auction->update(['status' => 'cancelled']);
                Log::info("Аукцион {$auction->number} отменён (нет заявок)");
            }
        }
        
        // 2. Торги, у которых прошло 20 минут с последней ставки → закрываем
        $expiredTrading = Auction::where('status', 'trading')
            ->whereNotNull('last_bid_at')
            ->where('last_bid_at', '<=', $now->copy()->subMinutes(20))
            ->get();
        
        foreach ($expiredTrading as $auction) {
            // Определяем победителя (минимальная цена)
            $winnerBid = $auction->tradingBids()
                ->orderBy('price', 'asc')
                ->first();
            
            if ($winnerBid) {
                $winnerBid->update(['status' => 'winner']);
                $auction->update([
                    'status' => 'closed',
                    'winner_company_id' => $winnerBid->company_id,
                ]);
                
                // Генерация PDF-протокола
                dispatch(new \App\Jobs\GenerateAuctionProtocol($auction));
                
                Log::info("Аукцион {$auction->number} завершён. Победитель: {$winnerBid->company->name}");
            }
        }
        
        // 3. Торги без ставок в течение 24 часов после начала → отменяем
        $tradingWithoutBids = Auction::where('status', 'trading')
            ->whereNull('last_bid_at')
            ->where('trading_start', '<=', $now->copy()->subHours(24))
            ->get();
        
        foreach ($tradingWithoutBids as $auction) {
            $auction->update(['status' => 'cancelled']);
            Log::info("Аукцион {$auction->number} отменён (нет ставок в торгах)");
        }
        
        Log::info('UpdateAuctionStatuses: Проверка завершена');
    }
}