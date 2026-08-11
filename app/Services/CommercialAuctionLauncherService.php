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
 * Создаёт связанный аукцион: НМЦ = средняя цена этапа 1 (#202), переносит веса/шаги/
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

        // #218 Предложения отстранённых компаний аннулированы: они не влияют на НМЦ
        // этапа 2 и такие компании не приглашаются к торгам.
        $bids = $rfq->bids()->where('status', '!=', 'rejected')->get();

        // Нет участников — процедура несостоялась.
        if ($bids->isEmpty()) {
            Log::warning("Коммерческий аукцион: RFQ #{$rfq->number} — нет участников, этап 2 не запускается.");

            return null;
        }

        // #202 НМЦ этапа 2 = среднее по всем предложениям этапа 1 (ранее — максимум).
        $startingPrice = round((float) $bids->avg('price'), 2);

        // #222 Торги этапа 2 стартуют в назначенное организатором время (trading_start), а не сразу
        // по завершении приёма заявок этапа 1. Если время начала уже наступило — стартуем сразу.
        $startsTradingNow = ! $rfq->trading_start || ! $rfq->trading_start->isFuture();

        return DB::transaction(function () use ($rfq, $bids, $startingPrice, $startsTradingNow) {
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
                // trading_end не задаётся: торги закрываются через 20 мин после последнего предложения.
                'starting_price' => $startingPrice,
                'weight_price' => $rfq->weight_price,
                'weight_deadline' => $rfq->weight_deadline,
                'weight_advance' => $rfq->weight_advance,
                'step_price' => $rfq->step_price,
                'step_deadline' => $rfq->step_deadline,
                'step_advance' => $rfq->step_advance,
                // #210 Референсы нормировки (100% шкалы) задаёт организатор на этапе 1 — переносим в аукцион.
                'max_deadline' => $rfq->max_deadline,
                'max_advance' => $rfq->max_advance,
                // #237 Ход и итоги коммерческих торгов видны только организатору и участникам.
                'is_results_hidden' => true,
                // #222 До trading_start аукцион ждёт в статусе 'active' (участники известны, приёма
                // заявок нет). UpdateAuctionStatuses переведёт его в 'trading' в назначенное время.
                'status' => $startsTradingNow ? 'trading' : 'active',
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
