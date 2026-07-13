<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Auction;
use App\Models\Rfq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * #179 Авто-запуск этапа 2 (Коммерческий аукцион) по завершении этапа 1 (Запрос цен).
 *
 * Создаёт связанный аукцион: НМЦ = максимальная цена этапа 1, переносит веса/шаги/
 * референсы, приглашает всех участников этапа 1, стартует сразу в статусе trading.
 */
class CommercialAuctionLauncherService
{
    /**
     * Запустить этап 2 для завершившегося коммерческого RFQ.
     * Возвращает созданный аукцион или null, если участников не было (процедура несостоялась).
     */
    public function launch(Rfq $rfq): ?Auction
    {
        if (! $rfq->isCommercial()) {
            return null;
        }

        // Уже запущен — идемпотентность.
        if ($rfq->linked_auction_id) {
            return Auction::find($rfq->linked_auction_id);
        }

        $bids = $rfq->bids()->get();

        // Нет участников — процедура несостоялась.
        if ($bids->isEmpty()) {
            Log::warning("Коммерческий аукцион: RFQ #{$rfq->number} — нет участников, этап 2 не запускается.");

            return null;
        }

        $startingPrice = (float) $bids->max('price'); // НМЦ = максимальная цена этапа 1

        return DB::transaction(function () use ($rfq, $bids, $startingPrice) {
            $auction = Auction::create([
                'number' => Auction::generateNumber(),
                'title' => $rfq->title,
                'description' => $rfq->description,
                'company_id' => $rfq->company_id,
                'rfq_id' => $rfq->id,
                'created_by' => $rfq->created_by,
                'type' => $rfq->type,
                'procedure' => Auction::PROCEDURE_COMMERCIAL,
                'currency' => $rfq->currency ?? 'RUB',
                // Приёма заявок на этапе 2 нет — участники известны с этапа 1.
                'start_date' => $rfq->end_date,
                'end_date' => $rfq->end_date,
                'trading_start' => $rfq->trading_start,
                'trading_end' => $rfq->trading_end,
                'starting_price' => $startingPrice,
                'weight_price' => $rfq->weight_price,
                'weight_deadline' => $rfq->weight_deadline,
                'weight_advance' => $rfq->weight_advance,
                'step_price' => $rfq->step_price,
                'step_deadline' => $rfq->step_deadline,
                'step_advance' => $rfq->step_advance,
                'max_deadline' => $rfq->max_deadline,
                'max_advance' => $rfq->max_advance,
                'is_results_hidden' => $rfq->is_results_hidden,
                'status' => 'trading',
            ]);

            // Приглашаем всех участников этапа 1 (по уникальным компаниям).
            foreach ($bids->pluck('company_id')->unique() as $companyId) {
                $auction->invitations()->create([
                    'company_id' => $companyId,
                    'status' => 'accepted',
                ]);
            }

            $rfq->update(['linked_auction_id' => $auction->id]);

            Log::info("Коммерческий аукцион запущен: RFQ #{$rfq->number} → аукцион {$auction->number}, НМЦ {$startingPrice}, участников: ".$bids->pluck('company_id')->unique()->count());

            return $auction;
        });
    }
}
