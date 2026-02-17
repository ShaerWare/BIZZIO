<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CloseAuctionJob;
use Tests\TestCase;
use Carbon\Carbon;

class AuctionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->company = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);

        $this->company->assignModerator($this->user, 'owner');

        Storage::fake('public');
        Queue::fake();
    }

    /**
     * Создание тестового аукциона
     */
    protected function createAuction(array $attributes = []): Auction
    {
        return Auction::create(array_merge([
            'number' => Auction::generateNumber(),
            'title' => 'Тестовый аукцион',
            'description' => 'Описание тестового аукциона',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'trading_start' => now()->addDays(8),
            'starting_price' => 1000000,
            'step_percent' => 2.5,
            'status' => 'active',
        ], $attributes));
    }

    // ==========================================
    // CRUD: Просмотр списка и карточки аукциона
    // ==========================================

    public function test_guest_can_view_auction_list(): void
    {
        $this->createAuction();

        $response = $this->get(route('auctions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.index');
    }

    public function test_authenticated_user_can_view_open_auction(): void
    {
        $auction = $this->createAuction(['type' => 'open']);

        $response = $this->actingAs($this->user)
            ->get(route('auctions.show', $auction));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.show');
        $response->assertSee($auction->title);
    }

    public function test_auction_list_can_be_filtered_by_status(): void
    {
        $this->createAuction(['status' => 'active']);
        $this->createAuction(['status' => 'closed']);

        $response = $this->get(route('auctions.index', ['status' => 'active']));

        $response->assertStatus(200);
    }

    public function test_auction_list_can_be_filtered_by_type(): void
    {
        $this->createAuction(['type' => 'open']);
        $this->createAuction(['type' => 'closed']);

        $response = $this->get(route('auctions.index', ['type' => 'open']));

        $response->assertStatus(200);
    }

    public function test_auction_list_can_be_searched(): void
    {
        $auction = $this->createAuction(['title' => 'Поставка строительных материалов']);

        $response = $this->get(route('auctions.index', ['search' => 'строительных']));

        $response->assertStatus(200);
    }

    // ==========================================
    // CRUD: Создание аукциона
    // ==========================================

    public function test_guest_cannot_create_auction(): void
    {
        $response = $this->get(route('auctions.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_company_cannot_create_auction(): void
    {
        $userWithoutCompany = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($userWithoutCompany)
            ->get(route('auctions.create'));

        $response->assertStatus(403);
    }

    public function test_company_moderator_can_view_create_auction_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('auctions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.create');
    }

    public function test_company_moderator_can_create_auction_as_draft(): void
    {
        $auctionData = [
            'title' => 'Новый аукцион',
            'description' => 'Описание нового аукциона',
            'company_id' => $this->company->id,
            'type' => 'open',
            'currency' => 'RUB',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'trading_start' => now()->addDays(8)->format('Y-m-d H:i:s'),
            'starting_price' => 1000000,
            'step_percent' => 2.5,
            'status' => 'draft',
            'notification_agreement' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('auctions.store'), $auctionData);

        $response->assertRedirect();

        $this->assertDatabaseHas('auctions', [
            'title' => 'Новый аукцион',
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);
    }

    public function test_auction_number_is_auto_generated(): void
    {
        $auctionData = [
            'title' => 'Аукцион с автономером',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'open',
            'currency' => 'RUB',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'trading_start' => now()->addDays(8)->format('Y-m-d H:i:s'),
            'starting_price' => 500000,
            'step_percent' => 1.5,
            'status' => 'draft',
            'notification_agreement' => true,
        ];

        $this->actingAs($this->user)
            ->post(route('auctions.store'), $auctionData);

        $auction = Auction::where('title', 'Аукцион с автономером')->first();

        $this->assertNotNull($auction->number);
        $this->assertStringStartsWith('А-', $auction->number);
    }

    // ==========================================
    // CRUD: Редактирование аукциона
    // ==========================================

    public function test_draft_auction_can_be_edited(): void
    {
        $auction = $this->createAuction(['status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->get(route('auctions.edit', $auction));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.edit');
    }

    public function test_active_auction_cannot_be_edited(): void
    {
        $auction = $this->createAuction(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('auctions.edit', $auction));

        $response->assertStatus(403);
    }

    public function test_non_moderator_cannot_edit_auction(): void
    {
        $otherUser = User::factory()->create();
        $auction = $this->createAuction(['status' => 'draft']);

        $response = $this->actingAs($otherUser)
            ->get(route('auctions.edit', $auction));

        $response->assertStatus(403);
    }

    // ==========================================
    // CRUD: Удаление аукциона
    // ==========================================

    public function test_draft_auction_can_be_deleted(): void
    {
        $auction = $this->createAuction(['status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->delete(route('auctions.destroy', $auction));

        $response->assertRedirect(route('auctions.index'));
        $this->assertSoftDeleted('auctions', ['id' => $auction->id]);
    }

    public function test_active_auction_cannot_be_deleted(): void
    {
        $auction = $this->createAuction(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->delete(route('auctions.destroy', $auction));

        $response->assertStatus(403);
    }

    // ==========================================
    // Типы аукционов (открытый/закрытый)
    // ==========================================

    public function test_closed_auction_can_have_invitations(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);

        $auctionData = [
            'title' => 'Закрытый аукцион',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'closed',
            'currency' => 'RUB',
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'trading_start' => now()->addDays(8)->format('Y-m-d H:i:s'),
            'starting_price' => 1000000,
            'step_percent' => 2.0,
            'status' => 'draft',
            'invited_companies' => [$participantCompany->id],
            'notification_agreement' => true,
        ];

        $this->actingAs($this->user)
            ->post(route('auctions.store'), $auctionData);

        $auction = Auction::where('title', 'Закрытый аукцион')->first();

        $this->assertCount(1, $auction->invitations);
        $this->assertEquals($participantCompany->id, $auction->invitations->first()->company_id);
    }

    public function test_uninvited_user_cannot_view_closed_auction(): void
    {
        $auction = $this->createAuction(['type' => 'closed', 'status' => 'active']);

        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $otherCompany = Company::factory()->create([
            'created_by' => $otherUser->id,
            'is_verified' => true,
        ]);
        $otherCompany->assignModerator($otherUser, 'owner');

        $response = $this->actingAs($otherUser)
            ->get(route('auctions.show', $auction));

        $response->assertStatus(403);
    }

    public function test_invited_user_can_view_closed_auction(): void
    {
        $auction = $this->createAuction(['type' => 'closed', 'status' => 'active']);

        $invitedUser = User::factory()->create(['email_verified_at' => now()]);
        $invitedCompany = Company::factory()->create([
            'created_by' => $invitedUser->id,
            'is_verified' => true,
        ]);
        $invitedCompany->assignModerator($invitedUser, 'owner');

        AuctionInvitation::create([
            'auction_id' => $auction->id,
            'company_id' => $invitedCompany->id,
        ]);

        $response = $this->actingAs($invitedUser)
            ->get(route('auctions.show', $auction));

        $response->assertStatus(200);
    }

    // ==========================================
    // Подача заявок
    // ==========================================

    public function test_company_can_submit_bid_to_open_auction(): void
    {
        $auction = $this->createAuction(['type' => 'open', 'status' => 'active']);

        $bidderUser = User::factory()->create();
        $bidderCompany = Company::factory()->create([
            'created_by' => $bidderUser->id,
            'is_verified' => true,
        ]);
        $bidderCompany->assignModerator($bidderUser, 'owner');

        $bidData = [
            'company_id' => $bidderCompany->id,
            'comment' => 'Готовы участвовать в аукционе',
            'acknowledgement' => true,
        ];

        $response = $this->actingAs($bidderUser)
            ->post(route('auctions.bids.store', $auction), $bidData);

        $response->assertRedirect();

        $this->assertDatabaseHas('auction_bids', [
            'auction_id' => $auction->id,
            'company_id' => $bidderCompany->id,
            'type' => 'initial',
        ]);
    }

    public function test_organizer_cannot_submit_bid_to_own_auction(): void
    {
        $auction = $this->createAuction(['type' => 'open', 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('auctions.show', $auction));

        // На странице не должна отображаться форма подачи заявки для организатора
        // Проверяем что компания организатора не в списке доступных компаний
        $response->assertViewHas('userCompanies', function ($companies) {
            return $companies->isEmpty() || !$companies->contains('id', $this->company->id);
        });
    }

    public function test_company_cannot_submit_duplicate_initial_bid(): void
    {
        $auction = $this->createAuction(['type' => 'open', 'status' => 'active']);

        $bidderUser = User::factory()->create();
        $bidderCompany = Company::factory()->create([
            'created_by' => $bidderUser->id,
            'is_verified' => true,
        ]);
        $bidderCompany->assignModerator($bidderUser, 'owner');

        // Создаём первую заявку
        AuctionBid::create([
            'auction_id' => $auction->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $bidderUser->id,
            'price' => $auction->starting_price,
            'type' => 'initial',
            'status' => 'pending',
        ]);

        // Пытаемся подать вторую заявку
        $response = $this->actingAs($bidderUser)
            ->post(route('auctions.bids.store', $auction), [
                'company_id' => $bidderCompany->id,
                'acknowledgement' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==========================================
    // Статусы аукциона
    // ==========================================

    public function test_auction_can_be_activated(): void
    {
        $auction = $this->createAuction(['status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->post(route('auctions.activate', $auction));

        $response->assertRedirect();
        $this->assertEquals('active', $auction->fresh()->status);
    }

    public function test_is_accepting_applications_works_correctly(): void
    {
        $auction = $this->createAuction([
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(5),
        ]);

        $this->assertTrue($auction->isAcceptingApplications());
    }

    public function test_expired_application_period_is_detected(): void
    {
        $auction = $this->createAuction([
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(3),
        ]);

        $this->assertFalse($auction->isAcceptingApplications());
    }

    public function test_trading_status_is_detected(): void
    {
        $auction = $this->createAuction(['status' => 'trading']);

        $this->assertTrue($auction->isTrading());
    }

    public function test_closed_status_is_detected(): void
    {
        $auction = $this->createAuction(['status' => 'closed']);

        $this->assertTrue($auction->isClosed());
    }

    // ==========================================
    // Цены и шаг аукциона
    // ==========================================

    public function test_current_price_returns_starting_price_when_no_bids(): void
    {
        $auction = $this->createAuction(['starting_price' => 500000]);

        $this->assertEquals(500000, $auction->getCurrentPrice());
    }

    public function test_current_price_returns_last_bid_price(): void
    {
        $auction = $this->createAuction(['starting_price' => 500000, 'status' => 'trading']);

        $bidder = User::factory()->create();
        $bidderCompany = Company::factory()->create(['is_verified' => true]);
        $bidderCompany->assignModerator($bidder, 'owner');

        AuctionBid::create([
            'auction_id' => $auction->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $bidder->id,
            'price' => 450000,
            'type' => 'bid',
            'status' => 'pending',
            'anonymous_code' => 'AB12',
        ]);

        $this->assertEquals(450000, $auction->getCurrentPrice());
    }

    public function test_step_range_is_calculated_correctly(): void
    {
        $auction = $this->createAuction(['starting_price' => 1000000]);

        $stepRange = $auction->getStepRange();

        $this->assertEquals(5000, $stepRange['min']); // 0.5% от 1M
        $this->assertEquals(50000, $stepRange['max']); // 5% от 1M
    }

    // ==========================================
    // Анонимные коды
    // ==========================================

    public function test_anonymous_code_is_generated(): void
    {
        $code = Auction::generateAnonymousCode();

        // 2 буквы + 2 цифры
        $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{2}$/', $code);
    }

    public function test_anonymous_codes_have_correct_structure(): void
    {
        // Генерируем несколько кодов и проверяем их структуру
        for ($i = 0; $i < 10; $i++) {
            $code = Auction::generateAnonymousCode();
            // Все коды должны иметь формат: 2 буквы + 2 цифры
            $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{2}$/', $code);
            // Код должен быть в верхнем регистре
            $this->assertEquals(strtoupper($code), $code);
        }
    }

    // ==========================================
    // Мои аукционы / Мои ставки
    // ==========================================

    public function test_user_can_view_my_auctions(): void
    {
        $this->createAuction();

        $response = $this->actingAs($this->user)
            ->get(route('auctions.my'));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.my-auctions');
    }

    public function test_user_can_view_my_bids(): void
    {
        $auction = $this->createAuction();

        // Создаём другую компанию для пользователя чтобы подать заявку
        $bidderCompany = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $bidderCompany->assignModerator($this->user, 'owner');

        AuctionBid::create([
            'auction_id' => $auction->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $this->user->id,
            'price' => $auction->starting_price,
            'type' => 'initial',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('auctions.bids.my'));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.my-bids');
    }

    public function test_user_can_view_my_invitations(): void
    {
        $auction = $this->createAuction(['type' => 'closed']);

        $invitedCompany = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $invitedCompany->assignModerator($this->user, 'owner');

        AuctionInvitation::create([
            'auction_id' => $auction->id,
            'company_id' => $invitedCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('auctions.invitations.my'));

        $response->assertStatus(200);
        $response->assertViewIs('auctions.my-invitations');
    }

    // ==========================================
    // Права доступа
    // ==========================================

    public function test_company_moderator_can_manage_auction(): void
    {
        $auction = $this->createAuction();

        $this->assertTrue($auction->canManage($this->user));
    }

    public function test_non_moderator_cannot_manage_auction(): void
    {
        $auction = $this->createAuction();
        $otherUser = User::factory()->create();

        $this->assertFalse($auction->canManage($otherUser));
    }

    // ==========================================
    // Протокол
    // ==========================================

    public function test_protocol_can_be_generated_for_closed_auction(): void
    {
        $auction = $this->createAuction(['status' => 'closed']);

        $response = $this->actingAs($this->user)
            ->post(route('auctions.protocol.generate', $auction));

        $response->assertRedirect();
        // Должно быть сообщение об успехе или ошибке
        $response->assertSessionHas('success');
    }

    public function test_protocol_cannot_be_generated_for_active_auction(): void
    {
        $auction = $this->createAuction(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->post(route('auctions.protocol.generate', $auction));

        $response->assertStatus(403);
    }

    public function test_non_organizer_cannot_generate_protocol(): void
    {
        $auction = $this->createAuction(['status' => 'closed']);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->post(route('auctions.protocol.generate', $auction));

        $response->assertStatus(403);
    }

    // ==========================================
    // Номер аукциона
    // ==========================================

    public function test_auction_number_format_is_correct(): void
    {
        $number = Auction::generateNumber();

        // Формат: А-ГГММДД-0001
        $this->assertMatchesRegularExpression('/^А-\d{6}-\d{4}$/', $number);
    }

    public function test_auction_numbers_are_sequential(): void
    {
        $number1 = Auction::generateNumber();
        $auction1 = $this->createAuction(['number' => $number1]);

        $number2 = Auction::generateNumber();

        // Извлекаем последовательные номера
        $seq1 = (int) substr($number1, -4);
        $seq2 = (int) substr($number2, -4);

        $this->assertEquals($seq1 + 1, $seq2);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function test_active_scope_returns_only_active_auctions(): void
    {
        $this->createAuction(['status' => 'active']);
        $this->createAuction(['status' => 'draft']);
        $this->createAuction(['status' => 'closed']);

        $activeAuctions = Auction::active()->get();

        $this->assertCount(1, $activeAuctions);
        $this->assertEquals('active', $activeAuctions->first()->status);
    }

    public function test_trading_scope_returns_only_trading_auctions(): void
    {
        $this->createAuction(['status' => 'active']);
        $this->createAuction(['status' => 'trading']);

        $tradingAuctions = Auction::trading()->get();

        $this->assertCount(1, $tradingAuctions);
        $this->assertEquals('trading', $tradingAuctions->first()->status);
    }

    public function test_closed_scope_returns_only_closed_auctions(): void
    {
        $this->createAuction(['status' => 'active']);
        $this->createAuction(['status' => 'closed']);

        $closedAuctions = Auction::closed()->get();

        $this->assertCount(1, $closedAuctions);
        $this->assertEquals('closed', $closedAuctions->first()->status);
    }

    public function test_search_scope_finds_by_title(): void
    {
        $this->createAuction(['title' => 'Поставка бетона']);
        $this->createAuction(['title' => 'Строительные работы']);

        $results = Auction::search('бетона')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Поставка бетона', $results->first()->title);
    }

    public function test_search_scope_finds_by_number(): void
    {
        $auction = $this->createAuction();

        $results = Auction::search($auction->number)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($auction->id, $results->first()->id);
    }
}
