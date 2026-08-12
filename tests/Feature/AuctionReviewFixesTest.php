<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Правки по замечаниям тестирования:
 * #269 — строка «Текущая цена» убрана из параметров аукциона (у коммерческого она была пустой);
 * #270 — веса, максимумы и шаги собраны в один блок «Параметры аукциона»;
 * #218 — история чата этапа 1 остаётся доступна на странице коммерческого аукциона
 *        организатору и участникам, но не посторонним.
 */
class AuctionReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $organizerCompany;

    private User $bidder;

    private Company $bidderCompany;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        [$this->organizer, $this->organizerCompany] = $this->makeUserWithCompany('Организатор ООО');
        [$this->bidder, $this->bidderCompany] = $this->makeUserWithCompany('Участник А');
    }

    /** @return array{0: User, 1: Company} */
    private function makeUserWithCompany(string $name): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create([
            'name' => $name,
            'created_by' => $user->id,
            'is_verified' => true,
        ]);
        $company->assignModerator($user, 'owner');

        return [$user, $company];
    }

    private function createStageOne(): Rfq
    {
        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Коммерческий аукцион',
            'company_id' => $this->organizerCompany->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'status' => 'active',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'trading_start' => now()->addDays(2),
            'step_price' => 1,
            'step_deadline' => 2,
            'step_advance' => 10,
            'max_deadline' => 30,
            'max_advance' => 40,
        ]);
    }

    private function createStageTwo(?Rfq $rfq = null, string $status = 'trading'): Auction
    {
        $auction = Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'Коммерческий аукцион — торги',
            'company_id' => $this->organizerCompany->id,
            'rfq_id' => $rfq?->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'status' => $status,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'trading_start' => now()->subMinute(),
            'starting_price' => 3850000,
            'step_percent' => 1,
            'weight_price' => 70,
            'weight_deadline' => 20,
            'weight_advance' => 10,
            'step_price' => 1,
            'step_deadline' => 2,
            'step_advance' => 10,
            'max_deadline' => 30,
            'max_advance' => 40,
            'is_results_hidden' => true,
        ]);

        $auction->invitations()->create([
            'company_id' => $this->bidderCompany->id,
            'status' => 'accepted',
        ]);

        $rfq?->update(['linked_auction_id' => $auction->id]);

        return $auction;
    }

    public function test_current_price_row_is_gone_from_auction_parameters(): void
    {
        $auction = $this->createStageTwo();

        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee('Текущая цена —');
    }

    public function test_parameters_block_shows_weights_maximums_and_steps_together(): void
    {
        $auction = $this->createStageTwo();

        $response = $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk();

        $response->assertSee('Параметры аукциона:');
        $response->assertSee('Веса: цена 70% / срок 20% / аванс 10%');
        $response->assertSee('Макс.: срок 30 дн. / аванс 40%');
        $response->assertSee('Шаг: цена 1% / срок 2 дн. / аванс 10%');

        // Отдельного блока «Параметры торгов (этап 2)» на странице аукциона больше нет.
        $response->assertDontSee('Параметры торгов (этап 2)');
    }

    public function test_stage_one_page_still_shows_maximums_and_steps(): void
    {
        $rfq = $this->createStageOne();

        $this->actingAs($this->organizer)
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee('Параметры торгов (этап 2)')
            ->assertSee('Макс.: срок 30 дн. / аванс 40%')
            ->assertSee('Шаг: цена 1% / срок 2 дн. / аванс 10%');
    }

    public function test_stage_one_chat_history_is_visible_on_commercial_auction_page(): void
    {
        $rfq = $this->createStageOne();

        RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $this->bidderCompany->id,
            'user_id' => $this->bidder->id,
            'price' => 1000,
            'deadline' => 30,
            'advance_percent' => 10,
            'status' => 'pending',
        ]);

        $this->actingAs($this->bidder)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Вопрос по документации'])
            ->assertCreated();

        $rfq->update(['status' => 'closed']);
        $auction = $this->createStageTwo($rfq);

        // Организатор видит блок чата на странице торгов.
        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Чат процедуры')
            ->assertSee('История переписки этапа 1');

        // Участник — тоже, и лента отдаёт его сообщение.
        $this->actingAs($this->bidder)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee('Чат процедуры');

        $this->actingAs($this->bidder)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Вопрос по документации')
            ->assertJsonPath('can_post', false);
    }

    public function test_outsider_does_not_see_chat_on_commercial_auction_page(): void
    {
        $rfq = $this->createStageOne();

        RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $this->bidderCompany->id,
            'user_id' => $this->bidder->id,
            'price' => 1000,
            'deadline' => 30,
            'advance_percent' => 10,
            'status' => 'pending',
        ]);

        $this->actingAs($this->bidder)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Секретный вопрос'])
            ->assertCreated();

        $rfq->update(['status' => 'closed']);
        $auction = $this->createStageTwo($rfq);

        [$stranger] = $this->makeUserWithCompany('Посторонняя компания');

        $this->actingAs($stranger)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee('Чат процедуры')
            ->assertDontSee('Секретный вопрос');

        $this->actingAs($stranger)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertForbidden();
    }
}
