<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionBid;

/**
 * #179 Движок оценки коммерческого аукциона (этап 2).
 *
 * В отличие от RfqScoringService (относительная нормировка по min/max набора),
 * здесь используются ФИКСИРОВАННЫЕ референсы, заданные организатором при создании,
 * чтобы баллы были сопоставимы во времени (принцип непрерывного лидерства).
 *
 * Нормировка — линейная от максимума-референса, единая для всех трёх критериев
 * (меньше значение → больше балл, диапазон 0..100):
 *   score(x, ref) = 100 * (ref - x) / ref
 * где для цены ref = НМЦ (starting_price), для срока ref = max_deadline,
 * для аванса ref = max_advance.
 *
 * Итог: S = (score_price*wp + score_deadline*wd + score_advance*wa) / 100.
 */
class CommercialAuctionScoringService
{
    /** Порог для сравнений с плавающей точкой (защита от ложных «ничьих»). */
    public const EPSILON = 1e-6;

    /**
     * Референсы нормировки аукциона.
     *
     * @return array{p0: float, dref: float, aref: float}
     */
    public function refs(Auction $auction): array
    {
        return [
            'p0' => (float) $auction->starting_price,
            'dref' => (float) $auction->max_deadline,
            'aref' => (float) $auction->max_advance,
        ];
    }

    /**
     * Веса критериев (в процентах, сумма 100).
     *
     * @return array{p: float, d: float, a: float}
     */
    public function weights(Auction $auction): array
    {
        return [
            'p' => (float) $auction->weight_price,
            'd' => (float) $auction->weight_deadline,
            'a' => (float) $auction->weight_advance,
        ];
    }

    /**
     * Нормированный балл одного критерия (0..100). Меньше значение → больше балл.
     */
    public function normalize(float $value, float $ref): float
    {
        if ($ref <= 0) {
            return 0.0;
        }

        return max(0.0, min(100.0, 100 * ($ref - $value) / $ref));
    }

    /**
     * Баллы по критериям и итоговый балл для набора (price, deadline, advance).
     *
     * @return array{price: float, deadline: float, advance: float, total: float}
     */
    public function computeScores(Auction $auction, float $price, float $deadline, float $advance): array
    {
        $refs = $this->refs($auction);
        $w = $this->weights($auction);

        $sp = $this->normalize($price, $refs['p0']);
        $sd = $this->normalize($deadline, $refs['dref']);
        $sa = $this->normalize($advance, $refs['aref']);

        $total = ($sp * $w['p'] + $sd * $w['d'] + $sa * $w['a']) / 100;

        return [
            'price' => $sp,
            'deadline' => $sd,
            'advance' => $sa,
            'total' => $total,
        ];
    }

    /**
     * Итоговый балл текущего «Лучшего предложения» (или null, если лидера нет).
     *
     * #206 Балл ПЕРЕСЧИТЫВАЕТСЯ из критериев лидера на полной точности, а не читается
     * из колонки total_score (decimal(8,4), округление). Иначе идентичное предложение,
     * посчитанное на полной точности, ложно «превосходит» округлённого лидера и
     * принимается — так проходили одинаковые предложения подряд.
     */
    public function bestScore(Auction $auction): ?float
    {
        $best = $auction->bestBid;

        if (! $best) {
            return null;
        }

        return $this->computeScores(
            $auction,
            (float) $best->price,
            (float) $best->deadline,
            (float) $best->advance_percent,
        )['total'];
    }

    /**
     * Превзойдёт ли предложение текущего лидера (строго лучше).
     */
    public function wouldBeat(Auction $auction, float $price, float $deadline, float $advance): bool
    {
        $best = $this->bestScore($auction);
        $total = $this->computeScores($auction, $price, $deadline, $advance)['total'];

        // Лидера нет — любое корректное предложение становится лучшим.
        if ($best === null) {
            return true;
        }

        return $total > $best + self::EPSILON;
    }

    /**
     * Полный анализ предложения: баллы, пер-критерий рекомендации/пороги,
     * общий вердикт и доступность кнопки «Подать предложение».
     *
     * @return array{
     *     scores: array{price: float, deadline: float, advance: float, total: float},
     *     best: array{price: float, deadline: float, advance: float, total: float}|null,
     *     criteria: array<string, array{is_best: bool, threshold: float|null, reachable: bool}>,
     *     total_score: float,
     *     best_score: float|null,
     *     deficit: float,
     *     would_beat: bool,
     *     submit_enabled: bool
     * }
     */
    public function analyze(Auction $auction, float $price, float $deadline, float $advance): array
    {
        $refs = $this->refs($auction);
        $w = $this->weights($auction);
        $scores = $this->computeScores($auction, $price, $deadline, $advance);

        $best = $auction->bestBid;
        // #206 Балл лидера пересчитываем на полной точности (см. bestScore()).
        $bestScore = $this->bestScore($auction);

        // Целевой итог, который нужно превзойти.
        $target = $bestScore !== null ? $bestScore + self::EPSILON : null;

        $deficit = $target !== null ? max(0.0, $target - $scores['total']) : 0.0;
        $wouldBeat = $target === null ? true : $scores['total'] > $target;

        $criteria = [
            'price' => $this->criterionAnalysis(
                $target, $scores, $w, 'p',
                $refs['p0'], $price, $best?->price !== null ? (float) $best->price : null
            ),
            'deadline' => $this->criterionAnalysis(
                $target, $scores, $w, 'd',
                $refs['dref'], $deadline, $best?->deadline !== null ? (float) $best->deadline : null
            ),
            'advance' => $this->criterionAnalysis(
                $target, $scores, $w, 'a',
                $refs['aref'], $advance, $best?->advance_percent !== null ? (float) $best->advance_percent : null
            ),
        ];

        return [
            'scores' => $scores,
            'best' => $best ? [
                'price' => (float) $best->price,
                'deadline' => (float) $best->deadline,
                'advance' => (float) $best->advance_percent,
                'total' => (float) $best->total_score,
            ] : null,
            'criteria' => $criteria,
            'total_score' => $scores['total'],
            'best_score' => $bestScore,
            'deficit' => $deficit,
            'would_beat' => $wouldBeat,
            'submit_enabled' => $wouldBeat,
        ];
    }

    /**
     * Анализ одного критерия: «Лучший критерий» (зелёная) и порог «Уменьшите до X».
     *
     * Порог — значение ЭТОГО критерия (при неизменных двух других), при котором
     * итог сравняется с целью. Замкнутая форма (нормировка линейна, S линейна по баллам).
     *
     * @param  array{price: float, deadline: float, advance: float, total: float}  $scores
     * @param  array{p: float, d: float, a: float}  $weights
     * @return array{is_best: bool, threshold: float|null, reachable: bool}
     */
    private function criterionAnalysis(
        ?float $target,
        array $scores,
        array $weights,
        string $key,
        float $ref,
        float $currentValue,
        ?float $bestValue,
    ): array {
        $scoreKeyMap = ['p' => 'price', 'd' => 'deadline', 'a' => 'advance'];
        $thisScore = $scores[$scoreKeyMap[$key]];
        $thisWeight = $weights[$key];

        // «Лучший критерий»: строго лучше (меньше), чем у лидера.
        $isBest = $bestValue !== null && $currentValue < $bestValue;

        // Порог считаем только если есть лидер и вес критерия > 0.
        if ($target === null || $thisWeight <= 0.0) {
            return ['is_best' => $isBest, 'threshold' => null, 'reachable' => false];
        }

        // Вклад двух других критериев (в единицах итогового балла * 100).
        $othersContribution = $scores['total'] * 100 - $thisScore * $thisWeight;

        // Требуемый балл этого критерия, чтобы итог = target.
        $targetScore = ($target * 100 - $othersContribution) / $thisWeight;

        // Недостижимо одним этим критерием (нужен балл > 100).
        if ($targetScore > 100 + self::EPSILON) {
            return ['is_best' => $isBest, 'threshold' => null, 'reachable' => false];
        }

        // Инвертируем нормировку: score = 100*(ref - x)/ref  →  x = ref*(1 - score/100).
        $threshold = $ref * (1 - max(0.0, $targetScore) / 100);

        return [
            'is_best' => $isBest,
            'threshold' => $threshold,
            'reachable' => true,
        ];
    }

    /**
     * Абсолютный шаг цены: организатор задаёт его в процентах от НМЦ.
     */
    public function priceStep(Auction $auction): float
    {
        return round((float) $auction->starting_price * (float) $auction->step_price / 100, 2);
    }

    /**
     * Шаги критериев в единицах самих критериев (цена — в валюте, срок — дни, аванс — п.п.).
     *
     * @return array{price: float, deadline: float, advance: float}
     */
    public function steps(Auction $auction): array
    {
        return [
            'price' => $this->priceStep($auction),
            'deadline' => (float) $auction->step_deadline,
            'advance' => (float) $auction->step_advance,
        ];
    }

    /**
     * Шаг = МИНИМАЛЬНОЕ УЛУЧШЕНИЕ относительно текущего лидера. Критерий можно оставить как
     * у лидера или ухудшить (компенсируя другими), но если участник его улучшает — не меньше
     * чем на один шаг. Мелкие «подрезания» на копейку запрещены.
     *
     * @return string|null Текст ошибки либо null, если нарушений нет
     */
    public function stepViolation(Auction $auction, AuctionBid $best, float $price, float $deadline, float $advance): ?string
    {
        $steps = $this->steps($auction);

        $checks = [
            ['цену', (float) $best->price, $price, $steps['price'], 2, ''],
            ['срок', (float) $best->deadline, $deadline, $steps['deadline'], 0, ' дн.'],
            ['аванс', (float) $best->advance_percent, $advance, $steps['advance'], 2, '%'],
        ];

        foreach ($checks as [$label, $bestValue, $value, $step, $precision, $unit]) {
            if ($step <= 0) {
                continue;
            }

            $improvement = $bestValue - $value;

            // Улучшения нет (значение как у лидера или хуже) — шаг не применяется.
            if ($improvement <= self::EPSILON) {
                continue;
            }

            if ($improvement < $step - self::EPSILON) {
                $allowed = max(0.0, $bestValue - $step);

                return sprintf(
                    'Улучшение по критерию «%s» должно быть не меньше шага (%s%s). Оставьте значение как у лидера (%s%s) или улучшите минимум до %s%s.',
                    $label,
                    $this->formatValue($step, $precision),
                    $unit,
                    $this->formatValue($bestValue, $precision),
                    $unit,
                    $this->formatValue($allowed, $precision),
                    $unit,
                );
            }
        }

        return null;
    }

    private function formatValue(float $value, int $precision): string
    {
        $formatted = number_format($value, $precision, '.', ' ');

        return $precision > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }

    /**
     * Записать вычисленные баллы в предложение (без сохранения).
     */
    public function fillScores(Auction $auction, AuctionBid $offer): void
    {
        $scores = $this->computeScores(
            $auction,
            (float) $offer->price,
            (float) $offer->deadline,
            (float) $offer->advance_percent,
        );

        $offer->score_price = $scores['price'];
        $offer->score_deadline = $scores['deadline'];
        $offer->score_advance = $scores['advance'];
        $offer->total_score = $scores['total'];
    }
}
