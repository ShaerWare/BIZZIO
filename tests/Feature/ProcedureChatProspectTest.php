<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #295 — Вопрос в чате закупки можно задать до подачи заявки: именно ответ организатора
 * помогает решить, участвовать ли. Отстранение такой компании работает так же, как для
 * участника (#218): чат и подача заявки блокируются.
 */
class ProcedureChatProspectTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $organizerCompany;

    private User $prospect;

    private Company $prospectCompany;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        [$this->organizer, $this->organizerCompany] = $this->makeUserWithCompany('Организатор ООО');
        [$this->prospect, $this->prospectCompany] = $this->makeUserWithCompany('Потенциальный участник');
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

    private function createRfq(string $type = 'open'): Rfq
    {
        return Rfq::create([
            'number' => Rfq::generateNumber(),
            'title' => 'Закупка с чатом',
            'company_id' => $this->organizerCompany->id,
            'created_by' => $this->organizer->id,
            'type' => $type,
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
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

    public function test_prospect_can_post_question_without_bid(): void
    {
        $rfq = $this->createRfq();

        $this->assertSame(0, RfqBid::where('rfq_id', $rfq->id)->count());

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Возможна ли поставка частями?'])
            ->assertCreated();

        $this->assertDatabaseHas('procedure_chat_messages', [
            'company_id' => $this->prospectCompany->id,
            'body' => 'Возможна ли поставка частями?',
        ]);
    }

    public function test_prospect_gets_anonymous_code_like_any_participant(): void
    {
        $rfq = $this->createRfq();

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Вопрос по ТЗ'])
            ->assertCreated()
            ->assertJsonPath('message.author', 'У-01');
    }

    public function test_prospect_does_not_see_company_names(): void
    {
        $rfq = $this->createRfq();
        [$other] = $this->makeUserWithCompany('Другая компания');

        $this->actingAs($other)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Вопрос от другой компании'])
            ->assertCreated();

        $message = $this->actingAs($this->prospect)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertOk()
            ->json('messages.0');

        $this->assertNull($message['company']);
        $this->assertSame('У-01', $message['author']);
    }

    public function test_closed_procedure_stays_invite_only(): void
    {
        $rfq = $this->createRfq('closed');

        $this->actingAs($this->prospect)
            ->getJson(route('rfqs.chat.index', $rfq))
            ->assertForbidden();

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Пустите в чат'])
            ->assertForbidden();
    }

    public function test_organizer_sees_prospect_and_can_ban_before_any_bid(): void
    {
        $rfq = $this->createRfq();

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Неподобающее сообщение'])
            ->assertCreated();

        // Компания без заявки видна организатору в списке участников чата.
        $this->assertTrue($rfq->chatVisibleCompanyIds()->contains($this->prospectCompany->id));

        $this->actingAs($this->organizer)
            ->postJson(route('rfqs.chat.ban', $rfq), [
                'company_id' => $this->prospectCompany->id,
                'reason' => 'Оскорбления в чате',
            ])
            ->assertOk();

        $this->assertTrue($rfq->fresh()->isCompanyBanned($this->prospectCompany->id));
    }

    public function test_banned_prospect_cannot_post_or_submit_bid(): void
    {
        $rfq = $this->createRfq();

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Первый вопрос'])
            ->assertCreated();

        $this->actingAs($this->organizer)
            ->postJson(route('rfqs.chat.ban', $rfq), [
                'company_id' => $this->prospectCompany->id,
                'reason' => 'Нарушение правил',
            ])
            ->assertOk();

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Ещё вопрос'])
            ->assertForbidden();
    }

    public function test_prospect_keeps_access_to_history_after_stage_one_closes(): void
    {
        $rfq = $this->createRfq();

        $this->actingAs($this->prospect)
            ->postJson(route('rfqs.chat.store', $rfq), ['body' => 'Вопрос до подачи заявки'])
            ->assertCreated();

        $rfq->update(['status' => 'closed']);

        $response = $this->actingAs($this->prospect)
            ->getJson(route('rfqs.chat.index', $rfq->fresh()))
            ->assertOk();

        $this->assertCount(1, $response->json('messages'));
        $this->assertFalse($response->json('can_post'), 'После закрытия этапа 1 чат только на чтение');
    }

    public function test_company_without_message_loses_access_after_stage_one_closes(): void
    {
        $rfq = $this->createRfq();
        $rfq->update(['status' => 'closed']);

        $this->actingAs($this->prospect)
            ->getJson(route('rfqs.chat.index', $rfq->fresh()))
            ->assertForbidden();
    }
}
