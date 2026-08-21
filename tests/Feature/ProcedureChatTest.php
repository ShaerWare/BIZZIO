<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use App\Services\CommercialAuctionLauncherService;
use App\Services\RfqScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #218 Чат процедуры: обезличивание участников и отстранение от участия организатором.
 */
class ProcedureChatTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $organizerCompany;

    private User $bidderA;

    private Company $companyA;

    private User $bidderB;

    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        [$this->organizer, $this->organizerCompany] = $this->makeUserWithCompany('Организатор ООО');
        [$this->bidderA, $this->companyA] = $this->makeUserWithCompany('Участник А');
        [$this->bidderB, $this->companyB] = $this->makeUserWithCompany('Участник Б');
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

    private function createRfq(string $procedure = Rfq::PROCEDURE_COMMERCIAL): Rfq
    {
        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Процедура с чатом',
            'company_id' => $this->organizerCompany->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => $procedure,
            'currency' => 'RUB',
            'status' => 'active',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'weight_price' => 100,
            'weight_deadline' => 0,
            'weight_advance' => 0,
            'trading_start' => now()->addDays(2),
            'step_price' => 0.5,
            'step_deadline' => 1,
            'step_advance' => 5,
            'max_deadline' => 90,
            'max_advance' => 100,
        ]);
    }

    private function addBid(Rfq $rfq, Company $company, User $user, float $price): RfqBid
    {
        return RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'price' => $price,
            'deadline' => 30,
            'advance_percent' => 10,
            'status' => 'pending',
        ]);
    }

    /**
     * #295 У ЗАКРЫТОЙ процедуры состав ограничен приглашёнными — правило #218 сохраняется.
     * Для открытой процедуры действует новое правило, см. ProcedureChatProspectTest.
     */
    public function test_outsider_cannot_read_chat_of_closed_procedure(): void
    {
        $rfq = $this->createRfq();
        $rfq->update(['type' => 'closed']);
        [$stranger] = $this->makeUserWithCompany('Посторонняя компания');

        $this->actingAs($stranger)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertForbidden();
    }

    public function test_guest_cannot_read_chat(): void
    {
        $rfq = $this->createRfq();

        $this->getJson(route('rfqs.chat.index', $rfq))->assertUnauthorized();
    }

    public function test_participant_sees_codes_and_not_company_names(): void
    {
        $rfq = $this->createRfq();
        $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);
        $this->addBid($rfq, $this->companyB, $this->bidderB, 1100);

        $this->actingAs($this->bidderA)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Когда будет ответ по ТЗ?'])
            ->assertCreated();

        $response = $this->actingAs($this->bidderB)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertOk();

        $message = $response->json('messages.0');

        $this->assertSame('У-01', $message['author']);
        $this->assertNull($message['company'], 'Участник не должен видеть название чужой компании');
        $this->assertNull($message['company_id']);
        $this->assertFalse($message['is_mine']);
    }

    public function test_organizer_sees_company_names(): void
    {
        $rfq = $this->createRfq();
        $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);

        $this->actingAs($this->bidderA)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Вопрос'])
            ->assertCreated();

        $response = $this->actingAs($this->organizer)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertOk();

        $this->assertSame('Участник А', $response->json('messages.0.company'));
        $this->assertTrue($response->json('is_organizer'));
    }

    public function test_chat_code_differs_from_stage_two_anonymous_code(): void
    {
        $rfq = $this->createRfq();
        $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);

        $this->actingAs($this->bidderA)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Вопрос'])
            ->assertCreated();

        $chatCode = $rfq->chatParticipantFor($this->companyA)->chat_code;

        // Код торгов этапа 2 — 2 буквы + 2 цифры; код чата умышленно другого формата.
        $this->assertMatchesRegularExpression('/^У-\d{2}$/u', $chatCode);
        $this->assertDoesNotMatchRegularExpression('/^[A-Z]{2}\d{2}$/', $chatCode);
    }

    public function test_organizer_bans_participant_and_bid_is_annulled(): void
    {
        $rfq = $this->createRfq();
        $bid = $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);

        $this->actingAs($this->organizer)
            ->postJson(route('rfqs.chat.ban', $rfq), [
                'company_id' => $this->companyA->id,
                'reason' => 'Компания отстранена за неподобающие комментарии',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue($rfq->isCompanyBanned($this->companyA->id));
        $this->assertSame('rejected', $bid->fresh()->status);

        // В чате появляется обезличенная системная запись с причиной.
        $systemMessage = $rfq->chatMessages()->where('is_system', true)->first();
        $this->assertNotNull($systemMessage);
        $this->assertStringContainsString('неподобающие комментарии', $systemMessage->body);
        $this->assertStringNotContainsString('Участник А', $systemMessage->body);
    }

    public function test_banned_participant_cannot_post_or_bid(): void
    {
        $rfq = $this->createRfq();
        $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);

        $this->actingAs($this->organizer)->postJson(route('rfqs.chat.ban', $rfq), [
            'company_id' => $this->companyA->id,
            'reason' => 'Нарушение правил',
        ])->assertOk();

        $this->actingAs($this->bidderA)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Ещё сообщение'])
            ->assertForbidden();

        $this->actingAs($this->bidderA)
            ->post(route('rfqs.bids.store', $rfq), [
                'company_id' => $this->companyA->id,
                'price' => 900,
                'deadline' => 10,
                'advance_percent' => 5,
            ])
            ->assertSessionHas('error');
    }

    public function test_ban_requires_reason_and_organizer_rights(): void
    {
        $rfq = $this->createRfq();
        $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);

        $this->actingAs($this->bidderA)
            ->postJson(route('rfqs.chat.ban', $rfq), [
                'company_id' => $this->companyB->id,
                'reason' => 'Просто так',
            ])
            ->assertForbidden();

        $this->actingAs($this->organizer)
            ->postJson(route('rfqs.chat.ban', $rfq), ['company_id' => $this->companyA->id])
            ->assertStatus(422);
    }

    public function test_banned_bid_is_excluded_from_winner_and_stage_two_price(): void
    {
        // Стандартный запрос цен: победителем не может стать отстранённая компания.
        $rfq = $this->createRfq(Rfq::PROCEDURE_STANDARD);
        $this->addBid($rfq, $this->companyA, $this->bidderA, 500);   // лучшая цена, но будет отстранена
        $this->addBid($rfq, $this->companyB, $this->bidderB, 1000);

        $this->actingAs($this->organizer)->postJson(route('rfqs.chat.ban', $rfq), [
            'company_id' => $this->companyA->id,
            'reason' => 'Нарушение правил',
        ])->assertOk();

        $scoring = app(RfqScoringService::class);
        $scoring->calculateScores($rfq);
        $winner = $scoring->determineWinner($rfq);

        $this->assertNotNull($winner);
        $this->assertSame($this->companyB->id, $winner->company_id);

        // Коммерческая процедура: НМЦ этапа 2 считается без аннулированных предложений.
        $commercial = $this->createRfq();
        $this->addBid($commercial, $this->companyA, $this->bidderA, 100);
        $this->addBid($commercial, $this->companyB, $this->bidderB, 900);

        $this->actingAs($this->organizer)->postJson(route('rfqs.chat.ban', $commercial), [
            'company_id' => $this->companyA->id,
            'reason' => 'Нарушение правил',
        ])->assertOk();

        $auction = app(CommercialAuctionLauncherService::class)->launch($commercial->fresh());

        $this->assertNotNull($auction);
        $this->assertEqualsWithDelta(900.0, (float) $auction->starting_price, 0.01);
        $this->assertFalse(
            $auction->invitations()->where('company_id', $this->companyA->id)->exists(),
            'Отстранённая компания не должна приглашаться на этап 2'
        );
    }

    public function test_chat_is_read_only_after_stage_one_closes(): void
    {
        $rfq = $this->createRfq();
        $this->addBid($rfq, $this->companyA, $this->bidderA, 1000);
        $rfq->update(['status' => 'closed']);

        $this->actingAs($this->bidderA)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertOk()
            ->assertJsonPath('can_post', false);

        $this->actingAs($this->bidderA)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Поздно'])
            ->assertStatus(422);
    }

    public function test_auction_chat_is_available_for_standard_auction(): void
    {
        $auction = Auction::create([
            'number' => Auction::generateNumber(),
            'title' => 'Аукцион с чатом',
            'company_id' => $this->organizerCompany->id,
            'created_by' => $this->organizer->id,
            'type' => 'open',
            'procedure' => Auction::PROCEDURE_STANDARD,
            'currency' => 'RUB',
            'status' => 'active',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'trading_start' => now()->addDays(2),
            'starting_price' => 100000,
            'step_percent' => 0.5,
        ]);

        AuctionBid::create([
            'auction_id' => $auction->id,
            'company_id' => $this->companyA->id,
            'user_id' => $this->bidderA->id,
            'price' => 100000,
            'type' => 'initial',
            'status' => 'pending',
        ]);

        $this->actingAs($this->bidderA)
            ->postJson(route('auctions.chat.store', $auction), ['body' => 'Вопрос по лоту'])
            ->assertCreated();

        $this->actingAs($this->organizer)
            ->getJson(route('auctions.chat.index', $auction))
            ->assertOk()
            ->assertJsonPath('messages.0.company', 'Участник А');
    }
}
