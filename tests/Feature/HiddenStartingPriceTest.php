<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #283 — По завершении закупки (этап 2 закрыт, протокол сформирован) НМЦ уходит из общего
 * доступа: её видят только организатор и участники закупки.
 */
class HiddenStartingPriceTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizer = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create(['created_by' => $this->organizer->id, 'is_verified' => true]);
        $this->company->assignModerator($this->organizer, 'owner');
        Storage::fake('public');
        Queue::fake();
    }

    /** @return array{0: User, 1: Company} */
    private function outsider(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);
        $company->assignModerator($user, 'owner');

        return [$user, $company];
    }

    private function auction(string $status): Auction
    {
        return Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'Закупка на монтаж',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'start_date' => now()->subDays(3),
            'end_date' => now()->subDays(2),
            'trading_start' => now()->subDay(),
            'trading_end' => now()->subHours(2),
            'starting_price' => 1_234_567,
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 100, 'max_advance' => 50,
            'status' => $status,
        ]);
    }

    public function test_starting_price_is_public_while_procedure_is_running(): void
    {
        $auction = $this->auction('trading');
        [$outsider] = $this->outsider();

        $this->assertFalse($auction->startingPriceHiddenFor($outsider));
        $this->assertFalse($auction->startingPriceHiddenFor(null));
    }

    public function test_starting_price_is_hidden_from_outsiders_after_close(): void
    {
        $auction = $this->auction('closed');
        [$outsider] = $this->outsider();

        $this->assertTrue($auction->startingPriceHiddenFor($outsider));
        $this->assertTrue($auction->startingPriceHiddenFor(null));
    }

    public function test_organizer_still_sees_starting_price_after_close(): void
    {
        $auction = $this->auction('closed');

        $this->assertFalse($auction->startingPriceHiddenFor($this->organizer));
    }

    public function test_invited_company_still_sees_starting_price_after_close(): void
    {
        $auction = $this->auction('closed');
        [$user, $company] = $this->outsider();
        $auction->invitations()->create(['company_id' => $company->id, 'status' => 'accepted']);

        $this->assertFalse($auction->startingPriceHiddenFor($user));
    }

    public function test_bidder_without_invitation_still_sees_starting_price_after_close(): void
    {
        $auction = $this->auction('closed');
        [$user, $company] = $this->outsider();
        AuctionBid::create([
            'auction_id' => $auction->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'initial',
            'price' => 1_000_000,
            'status' => 'pending',
        ]);

        $this->assertFalse($auction->startingPriceHiddenFor($user));
    }

    /** Формат x-money (#269): разряды разделяет неразрывный пробел. */
    private function formattedStartingPrice(): string
    {
        return number_format(1234567, 2, ',', "\u{00A0}");
    }

    public function test_card_hides_starting_price_from_outsider(): void
    {
        $auction = $this->auction('closed');
        [$outsider] = $this->outsider();

        $this->actingAs($outsider);
        $html = view('components.auction-card', ['auction' => $auction->fresh()])->render();

        $this->assertStringContainsString('скрыта', $html);
        $this->assertStringNotContainsString($this->formattedStartingPrice(), $html);
    }

    public function test_card_shows_starting_price_to_organizer(): void
    {
        $auction = $this->auction('closed');

        $this->actingAs($this->organizer);
        $html = view('components.auction-card', ['auction' => $auction->fresh()])->render();

        $this->assertStringContainsString($this->formattedStartingPrice(), $html);
    }

    public function test_auction_page_hides_starting_price_from_outsider(): void
    {
        $auction = $this->auction('closed');
        [$outsider] = $this->outsider();

        $response = $this->actingAs($outsider)->get(route('auctions.show', $auction));

        $response->assertOk();
        $response->assertSee('скрыта после завершения закупки');
        $response->assertDontSee($this->formattedStartingPrice(), false);
    }

    public function test_auction_page_shows_starting_price_to_organizer(): void
    {
        $auction = $this->auction('closed');

        $response = $this->actingAs($this->organizer)->get(route('auctions.show', $auction));

        $response->assertOk();
        $response->assertSee($this->formattedStartingPrice(), false);
    }
}
