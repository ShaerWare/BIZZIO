<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\CloseAuctionJob;
use App\Jobs\CloseRfqJob;
use App\Jobs\UpdateAuctionStatuses;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use App\Services\AuctionWinnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #179 Коммерческий аукцион — двухэтапная процедура (Запрос цен → Аукцион).
 */
class CommercialAuctionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $this->company->assignModerator($this->user, 'owner');

        Storage::fake('public');
        Queue::fake();
    }

    /** Данные формы создания коммерческого RFQ. */
    private function commercialRfqPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Коммерческий аукцион на поставку',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'open',
            'procedure' => 'commercial',
            'currency' => 'RUB',
            'status' => 'draft',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'trading_start' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'trading_end' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            'max_deadline' => 100,
            'max_advance' => 50,
            'technical_specification' => UploadedFile::fake()->createWithContent('tz.pdf', '%PDF-1.4 test'),
        ], $overrides);
    }

    public function test_creates_commercial_rfq_with_stage_2_config(): void
    {
        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $this->commercialRfqPayload())
            ->assertRedirect();

        $rfq = Rfq::where('title', 'Коммерческий аукцион на поставку')->first();

        $this->assertNotNull($rfq);
        $this->assertTrue($rfq->isCommercial());
        $this->assertSame(1, (int) $rfq->step_deadline);
        $this->assertSame(100, (int) $rfq->max_deadline);
        $this->assertEqualsWithDelta(0.5, (float) $rfq->step_price, 1e-9);
        $this->assertEqualsWithDelta(50.0, (float) $rfq->max_advance, 1e-9);
        $this->assertNotNull($rfq->trading_start);
        $this->assertNotNull($rfq->trading_end);
    }

    public function test_commercial_requires_stage_2_fields(): void
    {
        $payload = $this->commercialRfqPayload([
            'trading_start' => null,
            'step_price' => null,
            'max_deadline' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $payload)
            ->assertSessionHasErrors(['trading_start', 'step_price', 'max_deadline']);

        $this->assertDatabasemissing('rfqs', ['title' => 'Коммерческий аукцион на поставку']);
    }

    public function test_standard_rfq_ignores_stage_2_fields(): void
    {
        $payload = $this->commercialRfqPayload([
            'procedure' => 'standard',
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            // намеренно без параметров этапа 2 — для standard они не требуются
            'trading_start' => null,
            'trading_end' => null,
            'step_price' => null,
            'step_deadline' => null,
            'step_advance' => null,
            'max_deadline' => null,
            'max_advance' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $payload)
            ->assertRedirect();

        $rfq = Rfq::where('title', 'Коммерческий аукцион на поставку')->first();
        $this->assertNotNull($rfq);
        $this->assertFalse($rfq->isCommercial());
        $this->assertNull($rfq->trading_start);
    }

    public function test_stage_1_bid_is_price_only(): void
    {
        $rfq = $this->activeCommercialRfq();

        [$bidder, $bidderCompany] = $this->verifiedBidder();

        // Заявка без срока/аванса — на этапе 1 это допустимо.
        $this->actingAs($bidder)
            ->post(route('rfqs.bids.store', $rfq), [
                'company_id' => $bidderCompany->id,
                'price' => 900_000,
            ])
            ->assertRedirect();

        $bid = RfqBid::where('rfq_id', $rfq->id)->where('company_id', $bidderCompany->id)->first();
        $this->assertNotNull($bid);
        $this->assertEqualsWithDelta(900_000, (float) $bid->price, 1e-6);
        $this->assertNull($bid->deadline);
        $this->assertNull($bid->advance_percent);
    }

    /** Активный коммерческий RFQ (этап 1 идёт). */
    private function activeCommercialRfq(): Rfq
    {
        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Активный КА',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'trading_start' => now()->addDay()->addHour(),
            'trading_end' => now()->addDays(2),
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            'max_deadline' => 100,
            'max_advance' => 50,
            'status' => 'active',
        ]);
    }

    /** @return array{0: User, 1: Company} */
    private function verifiedBidder(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);
        $company->assignModerator($user, 'owner');

        return [$user, $company];
    }

    // =========================================================
    // Слайс 4 — авто-запуск этапа 2
    // =========================================================

    /** Коммерческий RFQ этапа 1 с истёкшим приёмом заявок и заданными ценами участников. */
    private function closeableCommercialRfq(array $prices): Rfq
    {
        $rfq = Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'КА для закрытия',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDays(2),
            'end_date' => now()->subMinute(),
            'trading_start' => now()->addMinutes(5),
            'trading_end' => now()->addDay(),
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            'max_deadline' => 100,
            'max_advance' => 50,
            'status' => 'active',
        ]);

        foreach ($prices as $price) {
            [$user, $company] = $this->verifiedBidder();
            RfqBid::create([
                'rfq_id' => $rfq->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'price' => $price,
                'status' => 'pending',
            ]);
        }

        return $rfq;
    }

    private function runCloseRfqJob(Rfq $rfq): void
    {
        app()->call([new CloseRfqJob($rfq), 'handle']);
    }

    public function test_closing_commercial_rfq_launches_stage_2_auction(): void
    {
        $rfq = $this->closeableCommercialRfq([900_000, 1_200_000, 1_000_000]);

        $this->runCloseRfqJob($rfq);
        $rfq->refresh();

        // Этап 1 закрыт, победитель/протокол НЕ формируются.
        $this->assertSame('closed', $rfq->status);
        $this->assertNull($rfq->winner_bid_id);

        // Связанный аукцион этапа 2 создан.
        $auction = $rfq->linkedAuction;
        $this->assertNotNull($auction);
        $this->assertTrue($auction->isCommercial());
        $this->assertSame('trading', $auction->status);
        // НМЦ = максимальная цена этапа 1.
        $this->assertEqualsWithDelta(1_200_000, (float) $auction->starting_price, 1e-6);
        // Перенос весов/шагов/референсов.
        $this->assertEqualsWithDelta(70, (float) $auction->weight_price, 1e-6);
        $this->assertSame(100, (int) $auction->max_deadline);
        $this->assertSame(1, (int) $auction->step_deadline);
        // Приглашены все 3 участника.
        $this->assertSame(3, $auction->invitations()->count());
        $this->assertSame($rfq->id, $auction->rfq_id);
    }

    public function test_closing_commercial_rfq_without_bids_does_not_launch(): void
    {
        $rfq = $this->closeableCommercialRfq([]);

        $this->runCloseRfqJob($rfq);
        $rfq->refresh();

        $this->assertSame('closed', $rfq->status);
        $this->assertNull($rfq->linked_auction_id);
        $this->assertSame(0, Auction::where('rfq_id', $rfq->id)->count());
    }

    public function test_update_statuses_closes_commercial_auction_past_trading_end(): void
    {
        Queue::fake();

        $auction = $this->tradingCommercialAuction(['trading_end' => now()->subMinute()]);

        (new UpdateAuctionStatuses)->handle();

        Queue::assertPushed(CloseAuctionJob::class);
    }

    public function test_commercial_winner_is_best_offer(): void
    {
        $auction = $this->tradingCommercialAuction();

        [$u1, $c1] = $this->verifiedBidder();
        $weak = AuctionBid::create([
            'auction_id' => $auction->id, 'company_id' => $c1->id, 'user_id' => $u1->id,
            'type' => 'offer', 'price' => 1_000_000, 'deadline' => 80, 'advance_percent' => 40,
            'total_score' => 15, 'anonymous_code' => 'AA11',
        ]);
        [$u2, $c2] = $this->verifiedBidder();
        $best = AuctionBid::create([
            'auction_id' => $auction->id, 'company_id' => $c2->id, 'user_id' => $u2->id,
            'type' => 'offer', 'price' => 800_000, 'deadline' => 50, 'advance_percent' => 20,
            'total_score' => 45, 'became_best_at' => now(), 'anonymous_code' => 'BB22',
        ]);
        $auction->update(['best_bid_id' => $best->id]);

        $winner = app(AuctionWinnerService::class)->determineWinner($auction->fresh());

        $this->assertNotNull($winner);
        $this->assertSame($best->id, $winner->id);
        $this->assertSame('closed', $auction->fresh()->status);
        $this->assertSame($best->id, (int) $auction->fresh()->winner_bid_id);
    }

    /** Коммерческий аукцион в статусе trading. */
    private function tradingCommercialAuction(array $overrides = []): Auction
    {
        return Auction::create(array_merge([
            'number' => Auction::generateNumber(),
            'title' => 'КА торги',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDay(),
            'end_date' => now()->subDay(),
            'trading_start' => now()->subHour(),
            'trading_end' => now()->addDay(),
            'starting_price' => 1_200_000,
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            'max_deadline' => 100,
            'max_advance' => 50,
            'status' => 'trading',
        ], $overrides));
    }
}
