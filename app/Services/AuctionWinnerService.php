<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionBid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuctionWinnerService
{
    /**
     * Определить победителя аукциона
     */
    public function determineWinner(Auction $auction): ?AuctionBid
    {
        // #179 Коммерческий аукцион: победитель — текущее «Лучшее предложение»
        // (лидер по совокупной оценке), а не последняя ставка.
        // #218 Ставки отстранённых компаний аннулированы (status=rejected) — победителем не становятся.
        $winnerBid = $auction->isCommercial()
            ? $auction->bestBid
            : $auction->tradingBids()->where('status', '!=', 'rejected')->first(); // Первая = последняя = минимальная цена

        if ($winnerBid && $winnerBid->status === 'rejected') {
            $winnerBid = null;
        }

        if (! $winnerBid) {
            Log::warning("Аукцион {$auction->number}: нет ставок в торгах.");

            return null;
        }

        DB::beginTransaction();

        try {
            // Обновляем статус заявки-победителя
            $winnerBid->update(['status' => 'winner']);

            // Обновляем аукцион
            $auction->update([
                'status' => 'closed',
                'winner_bid_id' => $winnerBid->id,
                'trading_end' => now(),
            ]);

            Log::info("Аукцион {$auction->number}: победитель определён. Компания: {$winnerBid->company->name}, Цена: {$winnerBid->price}");

            DB::commit();

            return $winnerBid;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ошибка при определении победителя аукциона {$auction->number}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Закрыть аукцион без победителя (если нет ставок)
     */
    public function closeWithoutWinner(Auction $auction): void
    {
        DB::beginTransaction();

        try {
            $auction->update([
                'status' => 'closed',
                'trading_end' => now(),
            ]);

            Log::info("Аукцион {$auction->number}: закрыт без победителя (нет ставок).");

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ошибка при закрытии аукциона {$auction->number}: ".$e->getMessage());
        }
    }
}
