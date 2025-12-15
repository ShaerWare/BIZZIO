<?php

namespace App\Services;

use App\Models\Rfq;
use App\Models\RfqBid;
use Illuminate\Support\Collection;

class RfqScoringService
{
    /**
     * Расчёт баллов для всех заявок RFQ
     */
    public function calculateScores(Rfq $rfq): void
    {
        $bids = $rfq->bids()->get();

        if ($bids->isEmpty()) {
            return;
        }

        // Расчёт баллов для каждой заявки
        foreach ($bids as $bid) {
            $scorePrice = $this->calculatePriceScore($bid, $bids);
            $scoreDeadline = $this->calculateDeadlineScore($bid, $bids);
            $scoreAdvance = $this->calculateAdvanceScore($bid, $bids);

            // Итоговый балл с учётом весов
            $totalScore = (
                ($scorePrice * $rfq->weight_price) +
                ($scoreDeadline * $rfq->weight_deadline) +
                ($scoreAdvance * $rfq->weight_advance)
            ) / 100;

            // Сохранение баллов
            $bid->update([
                'score_price' => $scorePrice,
                'score_deadline' => $scoreDeadline,
                'score_advance' => $scoreAdvance,
                'total_score' => $totalScore,
            ]);
        }
    }

    /**
     * Расчёт баллов за цену
     * Формула: 100 * (минимальная цена / цена заявки)
     */
    private function calculatePriceScore(RfqBid $bid, Collection $bids): float
    {
        $minPrice = $bids->min('price');

        if ($minPrice == 0 || $bid->price == 0) {
            return 0;
        }

        return min(100, (100 * $minPrice) / $bid->price);
    }

    /**
     * Расчёт баллов за срок выполнения
     * Формула: 100 * (минимальный срок / срок заявки)
     */
    private function calculateDeadlineScore(RfqBid $bid, Collection $bids): float
    {
        $minDeadline = $bids->min('deadline');

        if ($minDeadline == 0 || $bid->deadline == 0) {
            return 0;
        }

        return min(100, (100 * $minDeadline) / $bid->deadline);
    }

    /**
     * Расчёт баллов за размер аванса
     * Формула: (размер аванса / максимальный аванс) * 100
     * Чем меньше аванс, тем лучше для организатора
     */
    private function calculateAdvanceScore(RfqBid $bid, Collection $bids): float
    {
        $maxAdvance = $bids->max('advance_percent');

        if ($maxAdvance == 0) {
            return 100; // Если все аванс = 0, все получают 100 баллов
        }

        // Инвертируем: меньший аванс = больше баллов
        return 100 - (($bid->advance_percent / $maxAdvance) * 100);
    }

    /**
     * Определение победителя (заявка с максимальным итоговым баллом)
     */
    public function determineWinner(Rfq $rfq): ?RfqBid
    {
        return $rfq->bids()
            ->orderBy('total_score', 'desc')
            ->first();
    }
}