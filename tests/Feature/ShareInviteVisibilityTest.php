<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #193 Видимость блока «Поделиться» (готовый текст приглашения сторонней компании).
 *
 * Блок был закрыт политикой `update`, разрешающей правки только черновику, поэтому пропадал
 * сразу после публикации — то есть ровно тогда, когда приглашать участников и нужно.
 */
class ShareInviteVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Заголовок блока с готовым текстом приглашения. */
    private const BLOCK = 'Пригласить стороннюю компанию';

    private User $organizer;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create([
            'created_by' => $this->organizer->id,
            'is_verified' => true,
        ]);
        $this->company->assignModerator($this->organizer, 'owner');

        Storage::fake('public');
        Queue::fake();
    }

    private function rfq(array $overrides = []): Rfq
    {
        return Rfq::create(array_merge([
            'number' => Rfq::generateNumber(),
            'title' => 'Процедура',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'currency' => 'RUB',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'weight_price' => 50, 'weight_deadline' => 30, 'weight_advance' => 20,
            'status' => 'active',
        ], $overrides));
    }

    private function auction(array $overrides = []): Auction
    {
        return Auction::create(array_merge([
            'number' => Auction::generateNumber(),
            'title' => 'Аукцион',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'trading_start' => now()->addDays(2),
            'starting_price' => 1_000_000,
            'step_percent' => 1,
            'status' => 'active',
        ], $overrides));
    }

    private function outsider(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    // ==========================================================
    // Запрос цен / этап 1 коммерческого аукциона
    // ==========================================================

    public function test_organizer_sees_share_block_after_publication(): void
    {
        // Регрессия #193: с началом приёма предложений блок пропадал.
        $rfq = $this->rfq(['status' => 'active']);

        $this->actingAs($this->organizer)
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee(self::BLOCK);
    }

    public function test_organizer_sees_share_block_on_draft(): void
    {
        $rfq = $this->rfq(['status' => 'draft']);

        $this->actingAs($this->organizer)
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee(self::BLOCK);
    }

    public function test_organizer_sees_share_block_until_stage1_ends_in_commercial(): void
    {
        $rfq = $this->rfq([
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'status' => 'active',
            'trading_start' => now()->addDays(2),
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 90, 'max_advance' => 100,
        ]);

        $this->actingAs($this->organizer)
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee(self::BLOCK);
    }

    public function test_share_block_disappears_after_procedure_is_closed(): void
    {
        $rfq = $this->rfq(['status' => 'closed']);

        $this->actingAs($this->organizer)
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertDontSee(self::BLOCK);
    }

    public function test_other_user_sees_share_block_on_open_procedure(): void
    {
        $rfq = $this->rfq(['type' => 'open', 'status' => 'active']);

        $this->actingAs($this->outsider())
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee(self::BLOCK);
    }

    public function test_other_user_does_not_see_share_block_on_closed_type_procedure(): void
    {
        // Закрытая процедура — участников определяет организатор, посторонний не зовёт никого.
        $rfq = $this->rfq(['type' => 'closed', 'status' => 'active']);

        $this->actingAs($this->outsider())
            ->get(route('rfqs.show', $rfq))
            ->assertForbidden();
    }

    public function test_guest_does_not_see_share_block(): void
    {
        $rfq = $this->rfq(['type' => 'open', 'status' => 'active']);

        $this->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertDontSee(self::BLOCK);
    }

    // ==========================================================
    // Аукцион
    // ==========================================================

    public function test_organizer_sees_share_block_on_auction_accepting_applications(): void
    {
        $auction = $this->auction(['status' => 'active']);

        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertSee(self::BLOCK);
    }

    public function test_share_block_disappears_when_auction_starts_trading(): void
    {
        // Торги идут — новых участников уже не привлечь.
        $auction = $this->auction(['status' => 'trading', 'trading_start' => now()->subHour()]);

        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee(self::BLOCK);
    }

    public function test_commercial_stage2_auction_has_no_share_block(): void
    {
        // Состав участников этапа 2 зафиксирован на этапе 1 — приглашать со стороны некого.
        $auction = $this->auction([
            'procedure' => Auction::PROCEDURE_COMMERCIAL,
            'status' => 'active',
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 90, 'max_advance' => 100,
        ]);

        $this->actingAs($this->organizer)
            ->get(route('auctions.show', $auction))
            ->assertOk()
            ->assertDontSee(self::BLOCK);
    }
}
