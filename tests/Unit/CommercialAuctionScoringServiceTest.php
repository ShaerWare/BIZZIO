<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Services\CommercialAuctionScoringService;
use Tests\TestCase;

/**
 * #179 Юнит-тесты движка оценки коммерческого аукциона (без БД — чистая математика).
 */
class CommercialAuctionScoringServiceTest extends TestCase
{
    private CommercialAuctionScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CommercialAuctionScoringService;
    }

    /** Аукцион с фиксированными референсами и весами (без сохранения в БД). */
    private function auction(array $overrides = []): Auction
    {
        return new Auction(array_merge([
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'starting_price' => 1_000_000,   // P0 (НМЦ)
            'max_deadline' => 100,           // Dref, дней
            'max_advance' => 50,             // Aref, %
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
        ], $overrides));
    }

    private function bestBid(array $attrs): AuctionBid
    {
        return new AuctionBid(array_merge(['type' => 'offer'], $attrs));
    }

    public function test_normalize_linear_from_reference(): void
    {
        // Без границы (full=0): x=ref → 0, x=0 → 100, x=ref/2 → 50
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(100, 100), 1e-9);
        $this->assertEqualsWithDelta(100.0, $this->service->normalize(0, 100), 1e-9);
        $this->assertEqualsWithDelta(50.0, $this->service->normalize(50, 100), 1e-9);
        // clamp
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(150, 100), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(5, 0), 1e-9);
    }

    public function test_normalize_scales_to_calculated_boundary(): void
    {
        // #280 Граница полного балла — 50 при исходном 100: шкала сжимается вдвое.
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(100, 100, 50), 1e-9);
        $this->assertEqualsWithDelta(40.0, $this->service->normalize(80, 100, 50), 1e-9);
        $this->assertEqualsWithDelta(100.0, $this->service->normalize(50, 100, 50), 1e-9);
        // За границей балл не растёт (clamp сверху).
        $this->assertEqualsWithDelta(100.0, $this->service->normalize(30, 100, 50), 1e-9);
        // Ухудшение относительно исходного не даёт отрицательных баллов.
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(120, 100, 50), 1e-9);
        // Исходное значение уже на границе (A0 = 0) — полный балл, без деления на ноль.
        $this->assertEqualsWithDelta(100.0, $this->service->normalize(0, 0, 0), 1e-9);
    }

    public function test_compute_scores_matches_hand_calculation(): void
    {
        $auction = $this->auction();

        // #280 Границы полного балла: цена 600_000 (−40%), срок 50 дн. (−50%), аванс 0%.
        // price=800000 → sp = 100*(1_000_000-800_000)/(1_000_000-600_000) = 50
        // deadline=60 → sd = 100*(100-60)/(100-50) = 80
        // advance=25 → sa = 100*(50-25)/50 = 50
        // total = (50*70 + 80*20 + 50*10)/100 = (3500+1600+500)/100 = 56
        $scores = $this->service->computeScores($auction, 800_000, 60, 25);

        $this->assertEqualsWithDelta(50.0, $scores['price'], 1e-9);
        $this->assertEqualsWithDelta(80.0, $scores['deadline'], 1e-9);
        $this->assertEqualsWithDelta(50.0, $scores['advance'], 1e-9);
        $this->assertEqualsWithDelta(56.0, $scores['total'], 1e-9);
    }

    public function test_initial_values_score_zero(): void
    {
        // #280 Критерии на исходных значениях — 0 баллов по каждому.
        $scores = $this->service->computeScores($this->auction(), 1_000_000, 100, 50);

        $this->assertEqualsWithDelta(0.0, $scores['price'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $scores['deadline'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $scores['advance'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $scores['total'], 1e-9);
    }

    public function test_boundary_values_give_weights_and_do_not_grow_further(): void
    {
        $auction = $this->auction();

        // Ровно на границах: цена −40%, срок −50%, аванс 0% → сумма баллов = сумме весов (100).
        $atBoundary = $this->service->computeScores($auction, 600_000, 50, 0);

        $this->assertEqualsWithDelta(100.0, $atBoundary['price'], 1e-9);
        $this->assertEqualsWithDelta(100.0, $atBoundary['deadline'], 1e-9);
        $this->assertEqualsWithDelta(100.0, $atBoundary['advance'], 1e-9);
        $this->assertEqualsWithDelta(100.0, $atBoundary['total'], 1e-9);

        // Дальнейшее улучшение баллов не добавляет.
        $beyond = $this->service->computeScores($auction, 300_000, 10, 0);
        $this->assertEqualsWithDelta(100.0, $beyond['total'], 1e-9);
    }

    public function test_zero_initial_advance_gives_full_advance_score(): void
    {
        // #280 Частный случай ТЗ: A0 = 0% — критерий уже на границе, всем даётся полный вес.
        $auction = $this->auction(['max_advance' => 0]);

        $scores = $this->service->computeScores($auction, 1_000_000, 100, 0);

        $this->assertEqualsWithDelta(100.0, $scores['advance'], 1e-9);
        // Цена и срок на исходных значениях → итог равен только весу аванса.
        $this->assertEqualsWithDelta(10.0, $scores['total'], 1e-9);
    }

    public function test_reference_example_from_specification(): void
    {
        // #280 Проверочный пример ТЗ — аукцион № А-260812-0001.
        $auction = $this->auction([
            'starting_price' => 10_849_435.79,
            'max_deadline' => 30,
            'max_advance' => 60,
            'weight_price' => 80,
            'weight_deadline' => 10,
            'weight_advance' => 10,
        ]);

        $apeiron = $this->service->computeScores($auction, 9_165_000, 28, 30);
        $signal = $this->service->computeScores($auction, 8_940_000, 28, 40);

        $this->assertEqualsWithDelta(37.38, round($apeiron['total'], 2), 1e-9);
        $this->assertEqualsWithDelta(39.87, round($signal['total'], 2), 1e-9);

        // Ключевое ожидание: преимущество по цене больше не перекрывается снижением аванса.
        $this->assertGreaterThan($apeiron['total'], $signal['total']);
    }

    public function test_would_beat_true_when_no_leader(): void
    {
        $auction = $this->auction();
        $auction->setRelation('bestBid', null);

        $this->assertTrue($this->service->wouldBeat($auction, 999_999, 100, 50));
    }

    public function test_would_beat_requires_strictly_better(): void
    {
        $auction = $this->auction();
        // best total = 56 (см. предыдущий расчёт)
        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 800_000, 'deadline' => 60, 'advance_percent' => 25, 'total_score' => 56,
        ]));

        // Идентичное предложение (total=56) — не строго лучше → отклонить.
        $this->assertFalse($this->service->wouldBeat($auction, 800_000, 60, 25));
        // Ниже цена → total выше → принять.
        $this->assertTrue($this->service->wouldBeat($auction, 700_000, 60, 25));
        // Хуже → отклонить.
        $this->assertFalse($this->service->wouldBeat($auction, 900_000, 60, 25));
    }

    public function test_identical_offer_rejected_even_if_stored_score_rounded_down(): void
    {
        // #206 Регресс: total_score в БД — decimal(8,4). Если сравнивать кандидата
        // (полная точность) с округлённым total_score лидера, идентичное предложение
        // ложно «превосходит» и принимается. Балл лидера должен пересчитываться из критериев.
        $auction = $this->auction();

        // Настоящий балл лидера, посчитанный из его критериев.
        $trueScore = $this->service->computeScores($auction, 812_345, 63, 27)['total'];

        // Имитируем округление колонки decimal(8,4) вниз (как хранит БД).
        $rounded = floor($trueScore * 10_000) / 10_000;

        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 812_345, 'deadline' => 63, 'advance_percent' => 27, 'total_score' => $rounded,
        ]));

        // Идентичное предложение — строго НЕ лучше, должно быть отклонено.
        $this->assertFalse($this->service->wouldBeat($auction, 812_345, 63, 27));
    }

    public function test_analyze_price_threshold_makes_offer_beat_leader(): void
    {
        $auction = $this->auction();
        // #280 Критерии лидера дают total = (75*70 + 100*20 + 60*10)/100 = 78,5.
        // #206 Балл лидера пересчитывается из критериев (не из округлённого total_score).
        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 700_000, 'deadline' => 50, 'advance_percent' => 20, 'total_score' => 78.5,
        ]));

        $leaderScore = $this->service->computeScores($auction, 700_000, 50, 20)['total'];

        // Кандидат хуже лидера; проверяем порог по цене (срок/аванс фиксированы).
        $candidate = ['price' => 800_000, 'deadline' => 60, 'advance' => 25];
        $analysis = $this->service->analyze($auction, $candidate['price'], $candidate['deadline'], $candidate['advance']);

        $this->assertFalse($analysis['would_beat']);
        $priceCriterion = $analysis['criteria']['price'];

        if ($priceCriterion['reachable']) {
            // Подставляем порог цены при неизменных сроке/авансе — итог должен сравняться с целью.
            $total = $this->service->computeScores(
                $auction, $priceCriterion['threshold'], $candidate['deadline'], $candidate['advance']
            )['total'];

            $target = $leaderScore + CommercialAuctionScoringService::EPSILON;
            $this->assertEqualsWithDelta($target, $total, 1e-4);
        } else {
            $this->markTestSkipped('Порог по цене недостижим при данных весах — проверяется отдельным кейсом.');
        }
    }

    public function test_analyze_marks_best_criterion_green_when_strictly_lower(): void
    {
        $auction = $this->auction();
        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 700_000, 'deadline' => 50, 'advance_percent' => 20, 'total_score' => 39,
        ]));

        // Цена ниже лидера, срок выше, аванс равен.
        $analysis = $this->service->analyze($auction, 600_000, 60, 20);

        $this->assertTrue($analysis['criteria']['price']['is_best']);     // 600k < 700k
        $this->assertFalse($analysis['criteria']['deadline']['is_best']); // 60 > 50
        $this->assertFalse($analysis['criteria']['advance']['is_best']);  // 20 == 20 (не строго меньше)
    }

    public function test_analyze_no_leader_enables_submit(): void
    {
        $auction = $this->auction();
        $auction->setRelation('bestBid', null);

        $analysis = $this->service->analyze($auction, 900_000, 80, 40);

        $this->assertNull($analysis['best']);
        $this->assertNull($analysis['best_score']);
        $this->assertTrue($analysis['would_beat']);
        $this->assertTrue($analysis['submit_enabled']);
        $this->assertSame(0.0, $analysis['deficit']);
    }

    public function test_analyze_reports_deficit_when_not_enough(): void
    {
        $auction = $this->auction();
        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 500_000, 'deadline' => 40, 'advance_percent' => 10, 'total_score' => 53,
        ]));

        // Слабое предложение — итог заметно ниже цели.
        $analysis = $this->service->analyze($auction, 950_000, 90, 45);

        $this->assertFalse($analysis['would_beat']);
        $this->assertFalse($analysis['submit_enabled']);
        $this->assertGreaterThan(0.0, $analysis['deficit']);
    }

    public function test_fill_scores_writes_all_four_scores(): void
    {
        $auction = $this->auction();
        $offer = $this->bestBid(['price' => 800_000, 'deadline' => 60, 'advance_percent' => 25]);

        $this->service->fillScores($auction, $offer);

        $this->assertEqualsWithDelta(50.0, (float) $offer->score_price, 1e-9);
        $this->assertEqualsWithDelta(80.0, (float) $offer->score_deadline, 1e-9);
        $this->assertEqualsWithDelta(50.0, (float) $offer->score_advance, 1e-9);
        $this->assertEqualsWithDelta(56.0, (float) $offer->total_score, 1e-9);
    }
}
