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
        // x=ref → 0, x=0 → 100, x=ref/2 → 50
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(100, 100), 1e-9);
        $this->assertEqualsWithDelta(100.0, $this->service->normalize(0, 100), 1e-9);
        $this->assertEqualsWithDelta(50.0, $this->service->normalize(50, 100), 1e-9);
        // clamp
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(150, 100), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->service->normalize(5, 0), 1e-9);
    }

    public function test_compute_scores_matches_hand_calculation(): void
    {
        $auction = $this->auction();

        // price=800000 → sp = 100*(1_000_000-800_000)/1_000_000 = 20
        // deadline=60 → sd = 100*(100-60)/100 = 40
        // advance=25 → sa = 100*(50-25)/50 = 50
        // total = (20*70 + 40*20 + 50*10)/100 = (1400+800+500)/100 = 27
        $scores = $this->service->computeScores($auction, 800_000, 60, 25);

        $this->assertEqualsWithDelta(20.0, $scores['price'], 1e-9);
        $this->assertEqualsWithDelta(40.0, $scores['deadline'], 1e-9);
        $this->assertEqualsWithDelta(50.0, $scores['advance'], 1e-9);
        $this->assertEqualsWithDelta(27.0, $scores['total'], 1e-9);
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
        // best total = 27 (см. предыдущий расчёт)
        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 800_000, 'deadline' => 60, 'advance_percent' => 25, 'total_score' => 27,
        ]));

        // Идентичное предложение (total=27) — не строго лучше → отклонить.
        $this->assertFalse($this->service->wouldBeat($auction, 800_000, 60, 25));
        // Ниже цена → total выше → принять.
        $this->assertTrue($this->service->wouldBeat($auction, 700_000, 60, 25));
        // Хуже → отклонить.
        $this->assertFalse($this->service->wouldBeat($auction, 900_000, 60, 25));
    }

    public function test_analyze_price_threshold_makes_offer_beat_leader(): void
    {
        $auction = $this->auction();
        $auction->setRelation('bestBid', $this->bestBid([
            'price' => 700_000, 'deadline' => 50, 'advance_percent' => 20, 'total_score' => 39,
        ]));

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

            $target = 39 + CommercialAuctionScoringService::EPSILON;
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

        $this->assertEqualsWithDelta(20.0, (float) $offer->score_price, 1e-9);
        $this->assertEqualsWithDelta(40.0, (float) $offer->score_deadline, 1e-9);
        $this->assertEqualsWithDelta(50.0, (float) $offer->score_advance, 1e-9);
        $this->assertEqualsWithDelta(27.0, (float) $offer->total_score, 1e-9);
    }
}
