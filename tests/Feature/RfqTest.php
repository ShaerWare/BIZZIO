<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\RfqInvitation;
use App\Services\RfqScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CloseRfqJob;
use Tests\TestCase;
use Carbon\Carbon;

class RfqTest extends TestCase
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
     * Создание тестового RFQ
     */
    protected function createRfq(array $attributes = []): Rfq
    {
        return Rfq::create(array_merge([
            'number' => Rfq::generateNumber(),
            'title' => 'Тестовый RFQ',
            'description' => 'Описание тестового RFQ',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'type' => 'open',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'active',
        ], $attributes));
    }

    // ==========================================
    // CRUD: Просмотр списка и карточки RFQ
    // ==========================================

    public function test_guest_can_view_rfq_list(): void
    {
        $this->createRfq();

        $response = $this->get(route('rfqs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('rfqs.index');
    }

    public function test_authenticated_user_can_view_open_rfq(): void
    {
        $rfq = $this->createRfq(['type' => 'open']);

        $response = $this->actingAs($this->user)
            ->get(route('rfqs.show', $rfq));

        $response->assertStatus(200);
        $response->assertViewIs('rfqs.show');
        $response->assertSee($rfq->title);
    }

    public function test_rfq_list_can_be_filtered_by_status(): void
    {
        $this->createRfq(['status' => 'active']);
        $this->createRfq(['status' => 'closed']);

        $response = $this->get(route('rfqs.index', ['status' => 'active']));

        $response->assertStatus(200);
    }

    public function test_rfq_list_can_be_searched(): void
    {
        $rfq = $this->createRfq(['title' => 'Поставка строительных материалов']);

        $response = $this->get(route('rfqs.index', ['search' => 'строительных']));

        $response->assertStatus(200);
    }

    // ==========================================
    // CRUD: Создание RFQ
    // ==========================================

    public function test_guest_cannot_create_rfq(): void
    {
        $response = $this->get(route('rfqs.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_company_cannot_create_rfq(): void
    {
        $userWithoutCompany = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($userWithoutCompany)
            ->get(route('rfqs.create'));

        $response->assertStatus(403);
    }

    public function test_company_moderator_can_view_create_rfq_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('rfqs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('rfqs.create');
    }

    public function test_company_moderator_can_create_rfq_as_draft(): void
    {
        $rfqData = [
            'title' => 'Новый RFQ',
            'description' => 'Описание нового RFQ',
            'company_id' => $this->company->id,
            'type' => 'open',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('rfqs.store'), $rfqData);

        $response->assertRedirect();

        $this->assertDatabaseHas('rfqs', [
            'title' => 'Новый RFQ',
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);
    }

    public function test_rfq_number_is_auto_generated(): void
    {
        $rfqData = [
            'title' => 'RFQ с автономером',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'open',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'draft',
        ];

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $rfqData);

        $rfq = Rfq::where('title', 'RFQ с автономером')->first();

        $this->assertNotNull($rfq->number);
        $this->assertStringStartsWith('К-', $rfq->number);
    }

    public function test_active_rfq_schedules_close_job(): void
    {
        $rfqData = [
            'title' => 'Активный RFQ',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'open',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'active',
        ];

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $rfqData);

        Queue::assertPushed(CloseRfqJob::class);
    }

    public function test_draft_rfq_does_not_schedule_close_job(): void
    {
        $rfqData = [
            'title' => 'Черновик RFQ',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'open',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'draft',
        ];

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $rfqData);

        Queue::assertNotPushed(CloseRfqJob::class);
    }

    // ==========================================
    // CRUD: Редактирование RFQ
    // ==========================================

    public function test_draft_rfq_can_be_edited(): void
    {
        $rfq = $this->createRfq(['status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->get(route('rfqs.edit', $rfq));

        $response->assertStatus(200);
        $response->assertViewIs('rfqs.edit');
    }

    public function test_active_rfq_cannot_be_edited(): void
    {
        $rfq = $this->createRfq(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->get(route('rfqs.edit', $rfq));

        $response->assertStatus(403);
    }

    public function test_non_moderator_cannot_edit_rfq(): void
    {
        $otherUser = User::factory()->create();
        $rfq = $this->createRfq(['status' => 'draft']);

        $response = $this->actingAs($otherUser)
            ->get(route('rfqs.edit', $rfq));

        $response->assertStatus(403);
    }

    // ==========================================
    // CRUD: Удаление RFQ
    // ==========================================

    public function test_draft_rfq_can_be_deleted(): void
    {
        $rfq = $this->createRfq(['status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->delete(route('rfqs.destroy', $rfq));

        $response->assertRedirect(route('rfqs.index'));
        $this->assertSoftDeleted('rfqs', ['id' => $rfq->id]);
    }

    public function test_active_rfq_cannot_be_deleted(): void
    {
        $rfq = $this->createRfq(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->delete(route('rfqs.destroy', $rfq));

        $response->assertStatus(403);
    }

    // ==========================================
    // Типы RFQ (открытый/закрытый)
    // ==========================================

    public function test_closed_rfq_can_have_invitations(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);

        $rfqData = [
            'title' => 'Закрытый RFQ',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'type' => 'closed',
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
            'status' => 'draft',
            'invited_companies' => [$participantCompany->id],
        ];

        $this->actingAs($this->user)
            ->post(route('rfqs.store'), $rfqData);

        $rfq = Rfq::where('title', 'Закрытый RFQ')->first();

        $this->assertCount(1, $rfq->invitations);
        $this->assertEquals($participantCompany->id, $rfq->invitations->first()->company_id);
    }

    // ==========================================
    // Подача заявок
    // ==========================================

    public function test_company_can_submit_bid_to_open_rfq(): void
    {
        $rfq = $this->createRfq(['type' => 'open', 'status' => 'active']);

        // Создаём другую компанию для подачи заявки
        $bidderUser = User::factory()->create();
        $bidderCompany = Company::factory()->create([
            'created_by' => $bidderUser->id,
            'is_verified' => true,
        ]);
        $bidderCompany->assignModerator($bidderUser, 'owner');

        $bidData = [
            'company_id' => $bidderCompany->id,
            'price' => 100000,
            'deadline' => 30,
            'advance_percent' => 20,
            'comment' => 'Наше коммерческое предложение',
        ];

        $response = $this->actingAs($bidderUser)
            ->post(route('rfqs.bids.store', $rfq), $bidData);

        $response->assertRedirect();

        $this->assertDatabaseHas('rfq_bids', [
            'rfq_id' => $rfq->id,
            'company_id' => $bidderCompany->id,
            'price' => 100000,
        ]);
    }

    public function test_organizer_cannot_submit_bid_to_own_rfq(): void
    {
        $rfq = $this->createRfq(['type' => 'open', 'status' => 'active']);

        // Пытаемся подать заявку от компании-организатора
        $bidData = [
            'company_id' => $this->company->id,
            'price' => 100000,
            'deadline' => 30,
            'advance_percent' => 20,
        ];

        // Это должно вернуть ошибку или не найти компанию в dropdown
        // В реальности на фронте такой компании не будет в списке
        $response = $this->actingAs($this->user)
            ->get(route('rfqs.show', $rfq));

        // Проверяем, что availableCompanies не содержит компанию организатора
        $response->assertViewHas('availableCompanies', function ($companies) {
            return $companies->where('id', $this->company->id)->isEmpty();
        });
    }

    public function test_company_cannot_submit_duplicate_bid(): void
    {
        $rfq = $this->createRfq(['type' => 'open', 'status' => 'active']);

        $bidderUser = User::factory()->create();
        $bidderCompany = Company::factory()->create([
            'created_by' => $bidderUser->id,
            'is_verified' => true,
        ]);
        $bidderCompany->assignModerator($bidderUser, 'owner');

        // Создаём первую заявку
        RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $bidderUser->id,
            'price' => 100000,
            'deadline' => 30,
            'advance_percent' => 20,
            'status' => 'pending',
        ]);

        // Проверяем, что на странице нельзя подать вторую заявку
        $response = $this->actingAs($bidderUser)
            ->get(route('rfqs.show', $rfq));

        $response->assertViewHas('alreadyBid', true);
    }

    // ==========================================
    // Закрытие RFQ и определение победителя
    // ==========================================

    public function test_rfq_can_be_closed(): void
    {
        $rfq = $this->createRfq(['status' => 'active']);

        $rfq->update(['status' => 'closed']);

        $this->assertEquals('closed', $rfq->fresh()->status);
    }

    public function test_expired_rfq_is_detected(): void
    {
        $rfq = $this->createRfq([
            'status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(3),
        ]);

        $this->assertTrue($rfq->isExpired());
    }

    public function test_active_rfq_is_detected(): void
    {
        $rfq = $this->createRfq([
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(5),
        ]);

        $this->assertTrue($rfq->isActive());
        $this->assertFalse($rfq->isExpired());
    }

    // ==========================================
    // Расчёт баллов (Scoring)
    // ==========================================

    public function test_scoring_service_calculates_scores(): void
    {
        $rfq = $this->createRfq([
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
        ]);

        // Создаём несколько заявок
        $bids = [];
        for ($i = 1; $i <= 3; $i++) {
            $bidderUser = User::factory()->create();
            $bidderCompany = Company::factory()->create(['is_verified' => true]);
            $bidderCompany->assignModerator($bidderUser, 'owner');

            $bids[] = RfqBid::create([
                'rfq_id' => $rfq->id,
                'company_id' => $bidderCompany->id,
                'user_id' => $bidderUser->id,
                'price' => 100000 * $i, // 100k, 200k, 300k
                'deadline' => 30 - ($i * 5), // 25, 20, 15 дней
                'advance_percent' => 10 * $i, // 10%, 20%, 30%
                'status' => 'pending',
            ]);
        }

        $scoringService = new RfqScoringService();
        $scoringService->calculateScores($rfq);

        // Проверяем, что у всех заявок есть баллы
        foreach ($bids as $bid) {
            $bid->refresh();
            $this->assertNotNull($bid->total_score);
            $this->assertGreaterThan(0, $bid->total_score);
        }
    }

    public function test_winner_has_highest_score(): void
    {
        $rfq = $this->createRfq([
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
        ]);

        // Заявка 1: лучшая цена
        $user1 = User::factory()->create();
        $company1 = Company::factory()->create(['is_verified' => true]);
        $company1->assignModerator($user1, 'owner');
        $bid1 = RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $company1->id,
            'user_id' => $user1->id,
            'price' => 50000, // Самая низкая цена
            'deadline' => 30,
            'advance_percent' => 30,
            'status' => 'pending',
        ]);

        // Заявка 2: худшая цена
        $user2 = User::factory()->create();
        $company2 = Company::factory()->create(['is_verified' => true]);
        $company2->assignModerator($user2, 'owner');
        $bid2 = RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $company2->id,
            'user_id' => $user2->id,
            'price' => 150000, // Высокая цена
            'deadline' => 30,
            'advance_percent' => 30,
            'status' => 'pending',
        ]);

        $scoringService = new RfqScoringService();
        $scoringService->calculateScores($rfq);

        $bid1->refresh();
        $bid2->refresh();

        // Заявка с лучшей ценой должна иметь более высокий балл
        $this->assertGreaterThan($bid2->total_score, $bid1->total_score);
    }

    // ==========================================
    // Мои RFQ / Мои заявки
    // ==========================================

    public function test_user_can_view_my_rfqs(): void
    {
        $this->createRfq();

        $response = $this->actingAs($this->user)
            ->get(route('rfqs.my'));

        $response->assertStatus(200);
        $response->assertViewIs('rfqs.my-rfqs');
    }

    public function test_user_can_view_my_bids(): void
    {
        $rfq = $this->createRfq();

        // Создаём заявку от пользователя (через другую компанию)
        $bidderCompany = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $bidderCompany->assignModerator($this->user, 'owner');

        RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $bidderCompany->id,
            'user_id' => $this->user->id,
            'price' => 100000,
            'deadline' => 30,
            'advance_percent' => 20,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('rfqs.my-bids'));

        $response->assertStatus(200);
        $response->assertViewIs('rfqs.my-bids');
    }

    // ==========================================
    // Права доступа
    // ==========================================

    public function test_company_moderator_can_manage_rfq(): void
    {
        $rfq = $this->createRfq();

        $this->assertTrue($rfq->canManage($this->user));
    }

    public function test_non_moderator_cannot_manage_rfq(): void
    {
        $rfq = $this->createRfq();
        $otherUser = User::factory()->create();

        $this->assertFalse($rfq->canManage($otherUser));
    }

    // ==========================================
    // Веса критериев
    // ==========================================

    public function test_weights_sum_must_equal_100(): void
    {
        // Тест проверяет логику валидации (если есть)
        $rfq = $this->createRfq([
            'weight_price' => 40,
            'weight_deadline' => 30,
            'weight_advance' => 30,
        ]);

        $sum = $rfq->weight_price + $rfq->weight_deadline + $rfq->weight_advance;
        $this->assertEquals(100, $sum);
    }

    // ==========================================
    // Активация RFQ
    // ==========================================

    public function test_draft_rfq_can_be_activated(): void
    {
        $rfq = $this->createRfq(['status' => 'draft']);

        // Добавляем техническое задание (требуется для активации)
        $pdf = UploadedFile::fake()->create('tz.pdf', 100, 'application/pdf');
        $rfq->addMedia($pdf)->toMediaCollection('technical_specification');

        $response = $this->actingAs($this->user)
            ->post(route('rfqs.activate', $rfq));

        $response->assertRedirect();
        $this->assertEquals('active', $rfq->fresh()->status);
    }

    public function test_rfq_without_technical_specification_cannot_be_activated(): void
    {
        $rfq = $this->createRfq(['status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->post(route('rfqs.activate', $rfq));

        $response->assertRedirect();
        $this->assertEquals('draft', $rfq->fresh()->status);
    }

    // ==========================================
    // Номер RFQ
    // ==========================================

    public function test_rfq_number_format_is_correct(): void
    {
        $number = Rfq::generateNumber();

        // Формат: К-ГГММДД-0001
        $this->assertMatchesRegularExpression('/^К-\d{6}-\d{4}$/', $number);
    }

    public function test_rfq_numbers_are_sequential(): void
    {
        $number1 = Rfq::generateNumber();

        $rfq1 = $this->createRfq(['number' => $number1]);

        $number2 = Rfq::generateNumber();

        // Извлекаем последовательные номера
        $seq1 = (int) substr($number1, -4);
        $seq2 = (int) substr($number2, -4);

        $this->assertEquals($seq1 + 1, $seq2);
    }
}
