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
 * #280 Нормировка — линейная от исходного значения критерия до РАСЧЁТНОЙ ГРАНИЦЫ полного
 * балла (меньше значение → больше балл, диапазон 0..100):
 *   score(x, ref, full) = 100 * clamp((ref - x) / (ref - full), 0, 1)
 * где для цены ref = НМЦ (starting_price), для срока ref = max_deadline,
 * для аванса ref = max_advance, а границы полного балла — системные предустановки:
 *   цена — снижение на 40% (full = ref * 0,6), срок — на 50% (full = ref * 0,5),
 *   аванс — до 0% (full = 0).
 *
 * До #280 нормировка шла от всего исходного значения (full = 0 для всех критериев), из-за
 * чего «Срок» и «Аванс» влияли на исход непропорционально своим весам: снижение аванса на
 * 10 п.п. перекрывало заметное снижение цены при весах 80/10/10.
 *
 * Итог: S = (score_price*wp + score_deadline*wd + score_advance*wa) / 100.
 */
class CommercialAuctionScoringService
{
    /** Порог для сравнений с плавающей точкой (защита от ложных «ничьих»). */
    public const EPSILON = 1e-6;

    /**
     * #280 Расчётные границы полного балла (доля от исходного значения критерия).
     * Системные предустановки — организатор их не задаёт.
     */
    public const FULL_FACTOR_PRICE = 0.60;      // полный балл при снижении цены на 40%

    public const FULL_FACTOR_DEADLINE = 0.50;   // полный балл при сокращении срока вдвое

    public const FULL_FACTOR_ADVANCE = 0.0;     // полный балл при авансе 0%

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
     * #280 Границы полного балла критериев (в единицах самих критериев).
     *
     * @return array{p: float, d: float, a: float}
     */
    public function fullRefs(Auction $auction): array
    {
        $refs = $this->refs($auction);

        return [
            'p' => $refs['p0'] * self::FULL_FACTOR_PRICE,
            'd' => $refs['dref'] * self::FULL_FACTOR_DEADLINE,
            'a' => $refs['aref'] * self::FULL_FACTOR_ADVANCE,
        ];
    }

    /**
     * Нормированный балл одного критерия (0..100). Меньше значение → больше балл.
     *
     * @param  float  $ref  исходное значение критерия (0 баллов)
     * @param  float  $full  расчётная граница полного балла (100 баллов)
     */
    public function normalize(float $value, float $ref, float $full = 0.0): float
    {
        $span = $ref - $full;

        // #280 Исходное значение уже на границе (частный случай ТЗ: A0 = 0%) — критерий
        // считается достигшим полного балла, деления на ноль не выполняем.
        if ($span <= 0.0) {
            return $value <= $full + self::EPSILON ? 100.0 : 0.0;
        }

        return max(0.0, min(100.0, 100 * ($ref - $value) / $span));
    }

    /**
     * Баллы по критериям и итоговый балл для набора (price, deadline, advance).
     *
     * @return array{price: float, deadline: float, advance: float, total: float}
     */
    public function computeScores(Auction $auction, float $price, float $deadline, float $advance): array
    {
        $refs = $this->refs($auction);
        $full = $this->fullRefs($auction);
        $w = $this->weights($auction);

        $sp = $this->normalize($price, $refs['p0'], $full['p']);
        $sd = $this->normalize($deadline, $refs['dref'], $full['d']);
        $sa = $this->normalize($advance, $refs['aref'], $full['a']);

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
        $fullRefs = $this->fullRefs($auction);
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
                $refs['p0'], $fullRefs['p'], $price, $best?->price !== null ? (float) $best->price : null
            ),
            'deadline' => $this->criterionAnalysis(
                $target, $scores, $w, 'd',
                $refs['dref'], $fullRefs['d'], $deadline, $best?->deadline !== null ? (float) $best->deadline : null
            ),
            'advance' => $this->criterionAnalysis(
                $target, $scores, $w, 'a',
                $refs['aref'], $fullRefs['a'], $advance, $best?->advance_percent !== null ? (float) $best->advance_percent : null
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
        float $full,
        float $currentValue,
        ?float $bestValue,
    ): array {
        $scoreKeyMap = ['p' => 'price', 'd' => 'deadline', 'a' => 'advance'];
        $thisScore = $scores[$scoreKeyMap[$key]];
        $thisWeight = $weights[$key];

        // «Лучший критерий»: строго лучше (меньше), чем у лидера.
        $isBest = $bestValue !== null && $currentValue < $bestValue;

        // Порог считаем только если есть лидер, вес критерия > 0 и критерий вообще
        // улучшаемый (#280: при ref == full, например A0 = 0%, снижать уже нечего).
        if ($target === null || $thisWeight <= 0.0 || $ref - $full <= 0.0) {
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

        // #280 Инвертируем нормировку:
        //   score = 100*(ref - x)/(ref - full)  →  x = ref - (score/100)*(ref - full).
        $threshold = $ref - max(0.0, $targetScore) / 100 * ($ref - $full);

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

        // #261 Сравниваем в целых единицах последнего разряда (копейки / сотые процента / дни).
        // На float сравнение давало ложные срабатывания: 388 888,50 − 369 444,08 = 19 444,42, а
        // «шаг минус допуск» в двоичном виде оказывался чуть больше этой разности.
        // $tolerance — допуск в тех же единицах: шаг цены производный (процент от НМЦ), и клиент
        // с сервером округляют его независимо (19 444,425 → PHP 19 444,43, JS 19 444,42), поэтому
        // копеечное расхождение не должно отклонять честное снижение ровно на один шаг.
        $checks = [
            ['цену', (float) $best->price, $price, $steps['price'], 2, '', 1],
            ['срок', (float) $best->deadline, $deadline, $steps['deadline'], 0, ' дн.', 0],
            ['аванс', (float) $best->advance_percent, $advance, $steps['advance'], 2, '%', 1],
        ];

        foreach ($checks as [$label, $bestValue, $value, $step, $precision, $unit, $tolerance]) {
            if ($step <= 0) {
                continue;
            }

            $factor = 10 ** $precision;
            $improvement = (int) round(($bestValue - $value) * $factor);
            $stepUnits = (int) round($step * $factor);

            // Улучшения нет (значение как у лидера или хуже) — шаг не применяется.
            if ($improvement <= 0) {
                continue;
            }

            if ($improvement < $stepUnits - $tolerance) {
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
