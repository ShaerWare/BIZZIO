<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Правки после тестов канбана:
 *  - #231 «Мои заявки» → 500, если тендер заявки мягко удалён (сирота-связь).
 *  - #233 дублирование коммерческой процедуры (этап 1 RFQ + этап 2 Auction) в списках.
 * Плюс контроль, что закрытый коммерческий аукцион со скрытыми результатами
 * принимает первое предложение (счастливый путь #232).
 */
class BoardFixes231233Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create(['created_by' => $this->user->id, 'is_verified' => true]);
        $this->company->assignModerator($this->user, 'owner');
        Storage::fake('public');
        Queue::fake();
    }

    /** @return array{0: User, 1: Company} */
    private function verifiedBidder(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);
        $company->assignModerator($user, 'owner');

        return [$user, $company];
    }

    private function standardRfq(): Rfq
    {
        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'RFQ обычный',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_STANDARD,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'weight_price' => 50, 'weight_deadline' => 30, 'weight_advance' => 20,
            'status' => 'active',
        ]);
    }

    private function tradingCommercialAuction(array $overrides = []): Auction
    {
        return Auction::create(array_merge([
            'number' => Auction::generateNumber(),
            'title' => 'КА торги',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'closed',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDay(),
            'end_date' => now()->subDay(),
            'trading_start' => now()->subHour(),
            'trading_end' => now()->addDay(),
            'starting_price' => 1_200_000,
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 100, 'max_advance' => 50,
            'is_results_hidden' => true,
            'status' => 'trading',
        ], $overrides));
    }

    // ===================== #231 =====================

    public function test_231_my_bids_ok_with_active_tenders(): void
    {
        $rfq = $this->standardRfq();
        [$bidder, $bidderCompany] = $this->verifiedBidder();
        RfqBid::create([
            'rfq_id' => $rfq->id, 'company_id' => $bidderCompany->id, 'user_id' => $bidder->id,
            'price' => 100000, 'deadline' => 10, 'advance_percent' => 5, 'status' => 'pending',
        ]);

        $auction = $this->tradingCommercialAuction();
        $auction->invitations()->create(['company_id' => $bidderCompany->id, 'status' => 'accepted']);
        AuctionBid::create([
            'auction_id' => $auction->id, 'company_id' => $bidderCompany->id, 'user_id' => $bidder->id,
            'type' => 'offer', 'price' => 900000, 'deadline' => 40, 'advance_percent' => 10,
            'total_score' => 20, 'anonymous_code' => 'ZZ99',
        ]);

        $this->actingAs($bidder)->get(route('tenders.bids.my'))->assertOk();
    }

    public function test_231_my_bids_ok_when_parent_soft_deleted(): void
    {
        $rfq = $this->standardRfq();
        [$bidder, $bidderCompany] = $this->verifiedBidder();
        RfqBid::create([
            'rfq_id' => $rfq->id, 'company_id' => $bidderCompany->id, 'user_id' => $bidder->id,
            'price' => 100000, 'deadline' => 10, 'advance_percent' => 5, 'status' => 'pending',
        ]);

        $auction = $this->tradingCommercialAuction();
        $auction->invitations()->create(['company_id' => $bidderCompany->id, 'status' => 'accepted']);
        AuctionBid::create([
            'auction_id' => $auction->id, 'company_id' => $bidderCompany->id, 'user_id' => $bidder->id,
            'type' => 'offer', 'price' => 900000, 'deadline' => 40, 'advance_percent' => 10,
            'total_score' => 20, 'anonymous_code' => 'YY88',
        ]);

        // Организатор мягко удаляет оба тендера — заявки участника становятся сиротами.
        $rfq->delete();
        $auction->delete();

        // #231: страница не должна падать 500; сироты-заявки просто скрываются.
        $this->actingAs($bidder)->get(route('tenders.bids.my'))->assertOk();
    }

    // ===================== #233 =====================

    public function test_233_no_commercial_duplication_in_my_tenders(): void
    {
        $rfq = Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'КА двухэтапный',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'closed',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDays(2), 'end_date' => now()->subMinute(),
            'trading_start' => now()->addMinutes(5), 'trading_end' => now()->addDay(),
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 100, 'max_advance' => 50,
            'status' => 'closed',
        ]);
        $auction = $this->tradingCommercialAuction(['title' => 'КА двухэтапный', 'rfq_id' => $rfq->id]);
        $rfq->update(['linked_auction_id' => $auction->id]);

        $response = $this->actingAs($this->user)->get(route('tenders.my'));
        $response->assertOk();
        $count = substr_count($response->getContent(), 'КА двухэтапный');
        $this->assertSame(1, $count, "Ожидалась 1 карточка процедуры, найдено {$count}");
    }

    public function test_233_unlaunched_commercial_rfq_still_visible_in_catalog(): void
    {
        // Незапущенный коммерческий RFQ (идёт этап 1) остаётся в каталоге.
        Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'КА этап 1 активен',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDay(), 'end_date' => now()->addDay(),
            'trading_start' => now()->addDay()->addHour(), 'trading_end' => now()->addDays(2),
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 100, 'max_advance' => 50,
            'status' => 'active',
        ]);

        $this->get(route('tenders.index', ['procedure' => 'commercial']))
            ->assertOk()
            ->assertSee('КА этап 1 активен');
    }

    // ===================== #232 (контроль happy-path) =====================

    public function test_232_closed_hidden_first_offer_accepted(): void
    {
        $auction = $this->tradingCommercialAuction();
        [$user, $company] = $this->verifiedBidder();
        $auction->invitations()->create(['company_id' => $company->id, 'status' => 'accepted']);

        $this->actingAs($user)
            ->post(route('auctions.offers.store', $auction), [
                'company_id' => $company->id, 'price' => 1_000_000, 'deadline' => 60, 'advance_percent' => 30,
            ])
            ->assertRedirect();

        $this->assertSame(1, AuctionBid::where('auction_id', $auction->id)->count());
    }
}
