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
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            // #210 Организатор задаёт максимумы срока/аванса (референсы нормировки этапа 2).
            'max_deadline' => 90,
            'max_advance' => 100,
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
        $this->assertEqualsWithDelta(0.5, (float) $rfq->step_price, 1e-9);
        $this->assertNotNull($rfq->trading_start);
        // #210 max_deadline/max_advance задаёт организатор — референсы нормировки (100% шкалы) этапа 2.
        $this->assertSame(90, (int) $rfq->max_deadline);
        $this->assertEqualsWithDelta(100.0, (float) $rfq->max_advance, 1e-9);
        // trading_end не задаётся (торги закрываются через 20 мин после последнего предложения).
        $this->assertNull($rfq->trading_end);
    }

    public function test_commercial_requires_stage_2_fields(): void
    {
        $payload = $this->commercialRfqPayload([
            'trading_start' => null,
            'step_price' => null,
            'max_deadline' => null,
            'max_advance' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $payload)
            ->assertSessionHasErrors(['trading_start', 'step_price', 'max_deadline', 'max_advance']);

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
    private function closeableCommercialRfq(array $prices, ?\Carbon\Carbon $tradingStart = null): Rfq
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
            // #222 По умолчанию время торгов уже наступило → этап 2 стартует в 'trading' сразу.
            'trading_start' => $tradingStart ?? now()->subMinute(),
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
        // #202 НМЦ = средняя цена этапа 1: (900k + 1.2M + 1M) / 3 = 1 033 333.33.
        $this->assertEqualsWithDelta(1_033_333.33, (float) $auction->starting_price, 0.01);
        // Перенос весов/шагов/референсов. #210 max_deadline/max_advance переносятся из RFQ (задал организатор).
        $this->assertEqualsWithDelta(70, (float) $auction->weight_price, 1e-6);
        $this->assertSame(100, (int) $auction->max_deadline);
        $this->assertEqualsWithDelta(50.0, (float) $auction->max_advance, 1e-9);
        $this->assertSame(1, (int) $auction->step_deadline);
        // Приглашены все 3 участника.
        $this->assertSame(3, $auction->invitations()->count());
        $this->assertSame($rfq->id, $auction->rfq_id);
    }

    public function test_stage_2_waits_for_trading_start_then_updatestatuses_starts_it(): void
    {
        // #222 Если время начала торгов ещё не наступило — этап 2 создаётся в 'active' (ожидание),
        // а не сразу в 'trading'. UpdateAuctionStatuses стартует торги в назначенное время.
        $rfq = $this->closeableCommercialRfq([800_000, 900_000], now()->addMinutes(30));
        $this->runCloseRfqJob($rfq);
        $auction = $rfq->fresh()->linkedAuction;

        $this->assertNotNull($auction);
        $this->assertSame('active', $auction->status, 'До trading_start торги не идут');

        // Время ещё не пришло — статус не меняется.
        (new UpdateAuctionStatuses)->handle();
        $this->assertSame('active', $auction->fresh()->status);

        // Наступило время начала торгов.
        $auction->update(['trading_start' => now()->subMinute()]);
        (new UpdateAuctionStatuses)->handle();
        $this->assertSame('trading', $auction->fresh()->status, 'В назначенное время торги стартуют');
    }

    public function test_stage_2_starts_trading_immediately_when_trading_start_passed(): void
    {
        // Время начала торгов уже прошло на момент закрытия этапа 1 → стартуем сразу.
        $rfq = $this->closeableCommercialRfq([800_000], now()->subMinute());
        $this->runCloseRfqJob($rfq);

        $this->assertSame('trading', $rfq->fresh()->linkedAuction->status);
    }

    public function test_scheduler_command_starts_commercial_stage2_and_does_not_cancel_it(): void
    {
        // #222 Регресс: планировщик гоняет КОМАНДУ auctions:update-statuses. Она не должна
        // отменять коммерческий этап 2 из-за отсутствия initialBids (у этапа 2 их нет) —
        // при наступлении trading_start торги должны СТАРТОВАТЬ.
        $auction = $this->tradingCommercialAuction([
            'status' => 'active',
            'trading_start' => now()->subMinute(),
        ]);

        $this->artisan('auctions:update-statuses')->assertExitCode(0);

        $this->assertSame('trading', $auction->fresh()->status, 'Коммерческий этап 2 должен стартовать, а не отмениться');
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

    public function test_update_statuses_closes_commercial_auction_after_last_offer_idle(): void
    {
        Queue::fake();

        // #179 Коммерческий аукцион закрывается через 20 мин после последнего предложения (как обычный).
        $auction = $this->tradingCommercialAuction([
            'trading_end' => null,
            'last_bid_at' => now()->subMinutes(21),
        ]);

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

    // =========================================================
    // Слайс 5 — подача предложений (реал-тайм)
    // =========================================================

    public function test_first_offer_becomes_best_and_is_base(): void
    {
        $auction = $this->tradingCommercialAuction();
        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)
            ->post(route('auctions.offers.store', $auction), [
                'company_id' => $company->id,
                'price' => 1_000_000,
                'deadline' => 60,
                'advance_percent' => 30,
            ])
            ->assertRedirect();

        $offer = AuctionBid::where('auction_id', $auction->id)->where('company_id', $company->id)->first();
        $this->assertNotNull($offer);
        $this->assertTrue((bool) $offer->is_base);
        $this->assertNotNull($offer->became_best_at);
        $this->assertNotNull($offer->total_score);
        $this->assertSame($offer->id, (int) $auction->fresh()->best_bid_id);
    }

    public function test_worse_offer_is_rejected(): void
    {
        $auction = $this->tradingCommercialAuction();
        [$u1, $c1] = $this->participant($auction);
        [$u2, $c2] = $this->participant($auction);

        // Сильное лидирующее предложение.
        $this->actingAs($u1)->post(route('auctions.offers.store', $auction), [
            'company_id' => $c1->id, 'price' => 800_000, 'deadline' => 40, 'advance_percent' => 10,
        ])->assertRedirect();

        $bestId = $auction->fresh()->best_bid_id;

        // Заведомо худшее предложение другого участника — отклоняется.
        $this->actingAs($u2)->post(route('auctions.offers.store', $auction), [
            'company_id' => $c2->id, 'price' => 1_150_000, 'deadline' => 90, 'advance_percent' => 45,
        ])->assertSessionHas('error');

        $this->assertSame($bestId, $auction->fresh()->best_bid_id, 'Лидер не должен смениться на худшее предложение');
        $this->assertSame(0, AuctionBid::where('auction_id', $auction->id)->where('company_id', $c2->id)->count());
    }

    public function test_strictly_better_offer_takes_lead(): void
    {
        $auction = $this->tradingCommercialAuction();
        [$u1, $c1] = $this->participant($auction);
        [$u2, $c2] = $this->participant($auction);

        $this->actingAs($u1)->post(route('auctions.offers.store', $auction), [
            'company_id' => $c1->id, 'price' => 1_000_000, 'deadline' => 60, 'advance_percent' => 30,
        ])->assertRedirect();

        $this->actingAs($u2)->post(route('auctions.offers.store', $auction), [
            'company_id' => $c2->id, 'price' => 850_000, 'deadline' => 45, 'advance_percent' => 15,
        ])->assertRedirect();

        $best = $auction->fresh()->bestBid;
        $this->assertSame($c2->id, $best->company_id);
    }

    public function test_non_participant_cannot_submit_offer(): void
    {
        $auction = $this->tradingCommercialAuction();
        // Верифицированная компания, но НЕ приглашена (не участник этапа 1).
        [$user, $company] = $this->verifiedBidder();

        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 500_000, 'deadline' => 10, 'advance_percent' => 0,
        ])->assertSessionHas('error');

        $this->assertSame(0, AuctionBid::where('auction_id', $auction->id)->count());
    }

    public function test_organizer_references_are_fixed_and_not_changed_by_offers(): void
    {
        // #210 Референсы нормировки (max срок/аванс) заданы организатором и НЕ меняются предложениями.
        $auction = $this->tradingCommercialAuction(['max_deadline' => 100, 'max_advance' => 50]);
        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 900_000, 'deadline' => 60, 'advance_percent' => 30,
        ])->assertRedirect();

        $auction->refresh();
        // Референсы остались организаторскими (не подменились значениями первого предложения).
        $this->assertSame(100, (int) $auction->max_deadline);
        $this->assertEqualsWithDelta(50.0, (float) $auction->max_advance, 1e-9);
        $this->assertSame(1, AuctionBid::where('auction_id', $auction->id)->count());
    }

    public function test_offer_exceeding_organizer_max_is_rejected(): void
    {
        // #210 Срок/аванс не могут превышать организаторские максимумы.
        $auction = $this->tradingCommercialAuction(['max_deadline' => 100, 'max_advance' => 50]);
        [$user, $company] = $this->participant($auction);

        // Срок превышает максимум (120 > 100) — отклоняется.
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 900_000, 'deadline' => 120, 'advance_percent' => 30,
        ])->assertSessionHas('error');

        // Аванс превышает максимум (60 > 50) — отклоняется.
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 900_000, 'deadline' => 60, 'advance_percent' => 60,
        ])->assertSessionHas('error');

        $this->assertSame(0, AuctionBid::where('auction_id', $auction->id)->count());
    }

    public function test_commercial_show_page_renders_offer_block(): void
    {
        $auction = $this->tradingCommercialAuction();
        [$user, $company] = $this->participant($auction);

        $this->actingAs($user)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Коммерческий аукцион — торги')
            ->assertSee('История лучших предложений', false)
            ->assertSee('commercialAuction(', false);
    }

    public function test_get_state_returns_commercial_payload(): void
    {
        $auction = $this->tradingCommercialAuction();
        [$user, $company] = $this->participant($auction);

        // Одно принятое предложение → становится лучшим.
        $this->actingAs($user)->post(route('auctions.offers.store', $auction), [
            'company_id' => $company->id, 'price' => 900_000, 'deadline' => 50, 'advance_percent' => 20,
        ])->assertRedirect();

        $this->actingAs($user)
            ->getJson(route('auctions.state', $auction))
            ->assertOk()
            ->assertJsonPath('procedure', 'commercial')
            ->assertJsonPath('best_offer.price', 900000)
            ->assertJsonPath('weights.p', 70)
            // #210 Референс задан организатором (max_deadline=100 из хелпера), не первым предложением.
            ->assertJsonPath('refs.max_deadline', 100);
    }

    /**
     * Верифицированный участник аукциона (создаёт приглашение).
     *
     * @return array{0: User, 1: Company}
     */
    private function participant(Auction $auction): array
    {
        [$user, $company] = $this->verifiedBidder();
        $auction->invitations()->create(['company_id' => $company->id, 'status' => 'accepted']);

        return [$user, $company];
    }

    // =========================================================
    // Слайс 7 — каталог/навигация
    // =========================================================

    public function test_tenders_catalog_filters_by_procedure(): void
    {
        // Один коммерческий и один обычный активный RFQ.
        $commercial = $this->closeableCommercialRfq([500_000]);
        $commercial->update(['status' => 'active', 'end_date' => now()->addDay(), 'title' => 'КА в каталоге']);

        $standard = Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Обычный RFQ в каталоге',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_STANDARD,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'weight_price' => 50, 'weight_deadline' => 30, 'weight_advance' => 20,
            'status' => 'active',
        ]);

        // Фильтр commercial показывает только коммерческий.
        $this->get(route('tenders.index', ['procedure' => 'commercial']))
            ->assertOk()
            ->assertSee('КА в каталоге')
            ->assertDontSee('Обычный RFQ в каталоге');

        // Карточка коммерческого RFQ несёт метку этапа 1.
        $this->get(route('tenders.index', ['procedure' => 'commercial']))
            ->assertSee('Коммерческий аукцион · Этап 1');
    }

    public function test_stage_1_page_links_to_launched_auction(): void
    {
        $rfq = $this->closeableCommercialRfq([700_000, 900_000]);
        $this->runCloseRfqJob($rfq);
        $rfq->refresh();

        $this->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee('Перейти к аукциону')
            ->assertSee(route('auctions.show', $rfq->linked_auction_id), false)
            // #204 На этапе 1 у коммерческого аукциона показываем только количество
            // предложений (count-блок), без таблицы промежуточных результатов.
            ->assertSee('Подано предложений')
            ->assertSee('итоги определяются на этапе 2 (торги)');
    }

    public function test_stage2_status_endpoint_reports_launch(): void
    {
        $rfq = $this->closeableCommercialRfq([700_000, 900_000]);

        // #205 До закрытия этап 2 ещё не запущен.
        $this->getJson(route('rfqs.stage2-status', $rfq))
            ->assertOk()
            ->assertJson(['launched' => false, 'url' => null]);

        $this->runCloseRfqJob($rfq);
        $rfq->refresh();

        // После закрытия — запущен, отдаётся URL аукциона для авто-перехода.
        $this->getJson(route('rfqs.stage2-status', $rfq))
            ->assertOk()
            ->assertJson([
                'launched' => true,
                'url' => route('auctions.show', $rfq->linked_auction_id),
            ]);
    }

    // =========================================================
    // Слайс 6 — протокол
    // =========================================================

    public function test_commercial_close_generates_protocol_with_winner(): void
    {
        $auction = $this->tradingCommercialAuction(['trading_end' => now()->subMinute()]);
        [$user, $company] = $this->participant($auction);

        $offer = AuctionBid::create([
            'auction_id' => $auction->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'type' => 'offer', 'price' => 850_000, 'deadline' => 40, 'advance_percent' => 15,
            'total_score' => 55, 'became_best_at' => now(), 'anonymous_code' => 'CC33',
        ]);
        $auction->update(['best_bid_id' => $offer->id]);

        app()->call([new CloseAuctionJob($auction->id), 'handle']);

        $auction->refresh();
        $this->assertSame('closed', $auction->status);
        $this->assertSame($offer->id, (int) $auction->winner_bid_id);
        $this->assertNotNull($auction->getFirstMedia('protocol'), 'Протокол коммерческого аукциона должен быть сгенерирован');
    }

    // =========================================================
    // #216 — полноценное редактирование черновика
    // =========================================================

    /** Черновик коммерческого RFQ для тестов редактирования. */
    private function draftCommercialRfq(array $overrides = []): Rfq
    {
        return Rfq::create(array_merge([
            'number' => Rfq::generateNumber(),
            'title' => 'Черновик КА',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'trading_start' => now()->addDays(2)->addHour(),
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            'max_deadline' => 90,
            'max_advance' => 100,
            'status' => 'draft',
        ], $overrides));
    }

    public function test_draft_commercial_rfq_allows_editing_all_fields(): void
    {
        $rfq = $this->draftCommercialRfq();

        $this->actingAs($this->user)
            ->put(route('rfqs.update', $rfq), [
                'title' => 'Обновлённый КА',
                'description' => 'Новое описание',
                'type' => 'closed',
                'currency' => 'USD',
                'start_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'end_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'trading_start' => now()->addDays(4)->addHours(2)->format('Y-m-d H:i:s'),
                'weight_price' => 50,
                'weight_deadline' => 30,
                'weight_advance' => 20,
                'step_price' => 1.5,
                'step_deadline' => 3,
                'step_advance' => 7,
                'max_deadline' => 120,
                'max_advance' => 60,
            ])
            ->assertRedirect(route('rfqs.show', $rfq));

        $rfq->refresh();

        $this->assertSame('Обновлённый КА', $rfq->title);
        $this->assertSame('closed', $rfq->type);
        $this->assertSame('USD', $rfq->currency);
        $this->assertEqualsWithDelta(50.0, (float) $rfq->weight_price, 1e-9);
        $this->assertEqualsWithDelta(1.5, (float) $rfq->step_price, 1e-9);
        $this->assertSame(3, (int) $rfq->step_deadline);
        $this->assertSame(120, (int) $rfq->max_deadline);
        $this->assertEqualsWithDelta(60.0, (float) $rfq->max_advance, 1e-9);
        $this->assertTrue($rfq->trading_start->greaterThan($rfq->end_date));
    }

    public function test_draft_edit_rejects_weights_not_summing_to_100(): void
    {
        $rfq = $this->draftCommercialRfq();

        $this->actingAs($this->user)
            ->put(route('rfqs.update', $rfq), [
                'title' => 'Обновлённый КА',
                'weight_price' => 50,
                'weight_deadline' => 30,
                'weight_advance' => 30,
            ])
            ->assertSessionHasErrors('weights');

        $this->assertEqualsWithDelta(70.0, (float) $rfq->fresh()->weight_price, 1e-9);
    }

    public function test_draft_edit_rejects_trading_start_before_end_date(): void
    {
        $rfq = $this->draftCommercialRfq();

        $this->actingAs($this->user)
            ->put(route('rfqs.update', $rfq), [
                'title' => 'Обновлённый КА',
                'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'trading_start' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('trading_start');
    }

    public function test_active_rfq_cannot_be_updated(): void
    {
        // #216 PUT подчиняется той же политике, что и форма: после активации правки запрещены.
        $rfq = $this->draftCommercialRfq(['status' => 'active']);

        $this->actingAs($this->user)
            ->put(route('rfqs.update', $rfq), [
                'title' => 'Активный КА',
                'step_price' => 4.5,
                'max_deadline' => 365,
            ])
            ->assertForbidden();

        $rfq->refresh();

        $this->assertSame('Черновик КА', $rfq->title);
        $this->assertEqualsWithDelta(0.5, (float) $rfq->step_price, 1e-9);
        $this->assertSame(90, (int) $rfq->max_deadline);
    }

    public function test_edit_page_shows_all_stage_2_fields_for_draft(): void
    {
        $draft = $this->draftCommercialRfq();

        $this->actingAs($this->user)
            ->get(route('rfqs.edit', $draft))
            ->assertOk()
            ->assertSee('name="step_price"', false)
            ->assertSee('name="step_deadline"', false)
            ->assertSee('name="step_advance"', false)
            ->assertSee('name="max_deadline"', false)
            ->assertSee('name="max_advance"', false)
            ->assertSee('name="weight_price"', false)
            ->assertSee('name="trading_start"', false)
            ->assertSee('name="currency"', false)
            ->assertSee('name="type"', false);
    }

    public function test_edit_page_hides_stage_2_fields_for_standard_rfq(): void
    {
        $draft = $this->draftCommercialRfq([
            'procedure' => Rfq::PROCEDURE_STANDARD,
            'trading_start' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('rfqs.edit', $draft))
            ->assertOk()
            ->assertSee('name="weight_price"', false)
            ->assertDontSee('name="step_price"', false)
            ->assertDontSee('name="max_advance"', false);
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
