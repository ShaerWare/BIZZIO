<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Rfq;
use App\Models\RfqBid;
use App\Models\User;
use App\Notifications\CommercialStageOneStartedNotification;
use App\Notifications\CommercialStageTwoSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * #195 Уведомления о старте этапов коммерческого аукциона.
 */
class CommercialStageNotificationsTest extends TestCase
{
    use RefreshDatabase;

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

        Notification::fake();
    }

    private function commercialRfq(array $overrides = []): Rfq
    {
        return Rfq::create(array_merge([
            'number' => Rfq::generateNumber(),
            'title' => 'Коммерческий аукцион',
            'company_id' => $this->company->id,
            'created_by' => $this->organizer->id,
            'type' => 'closed',
            'procedure' => Rfq::PROCEDURE_COMMERCIAL,
            'currency' => 'RUB',
            'start_date' => now()->subMinute(),
            'end_date' => now()->addDay(),
            'trading_start' => now()->addDay()->addMinutes(10),
            'weight_price' => 70, 'weight_deadline' => 20, 'weight_advance' => 10,
            'step_price' => 0.5, 'step_deadline' => 1, 'step_advance' => 5,
            'max_deadline' => 90, 'max_advance' => 100,
            'status' => 'active',
        ], $overrides));
    }

    /** @return array{0: User, 1: Company} */
    private function invitedCompany(Rfq $rfq): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id, 'is_verified' => true]);
        $company->assignModerator($user, 'owner');

        $rfq->invitations()->create([
            'company_id' => $company->id,
            'invited_by' => $this->organizer->id,
            'status' => 'pending',
        ]);

        return [$user, $company];
    }

    private function bid(Rfq $rfq, Company $company, User $user): RfqBid
    {
        return RfqBid::create([
            'rfq_id' => $rfq->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'price' => 500_000,
            'status' => 'pending',
        ]);
    }

    // ==========================================================
    // Этап 1 — начало приёма предложений
    // ==========================================================

    public function test_stage1_start_notifies_moderators_of_invited_companies(): void
    {
        $rfq = $this->commercialRfq();
        [$invitedUser] = $this->invitedCompany($rfq);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertSentTo($invitedUser, CommercialStageOneStartedNotification::class);
        $this->assertNotNull($rfq->fresh()->stage1_notified_at);
    }

    public function test_stage1_notification_is_not_sent_before_start_date(): void
    {
        $rfq = $this->commercialRfq(['start_date' => now()->addHour()]);
        [$invitedUser] = $this->invitedCompany($rfq);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertNotSentTo($invitedUser, CommercialStageOneStartedNotification::class);
        $this->assertNull($rfq->fresh()->stage1_notified_at);
    }

    public function test_stage1_notification_is_sent_only_once(): void
    {
        $rfq = $this->commercialRfq();
        [$invitedUser] = $this->invitedCompany($rfq);

        $this->artisan('commercial:notify-stages')->assertSuccessful();
        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertSentToTimes($invitedUser, CommercialStageOneStartedNotification::class, 1);
    }

    public function test_stage1_notification_skips_draft_and_standard_procedures(): void
    {
        $draft = $this->commercialRfq(['status' => 'draft']);
        [$draftUser] = $this->invitedCompany($draft);

        $standard = $this->commercialRfq(['procedure' => Rfq::PROCEDURE_STANDARD, 'trading_start' => null]);
        [$standardUser] = $this->invitedCompany($standard);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertNotSentTo($draftUser, CommercialStageOneStartedNotification::class);
        Notification::assertNotSentTo($standardUser, CommercialStageOneStartedNotification::class);
    }

    // ==========================================================
    // Этап 2 — за 30 минут до начала торгов
    // ==========================================================

    public function test_stage2_notification_goes_to_bidders_30_minutes_before_trading(): void
    {
        $rfq = $this->commercialRfq(['trading_start' => now()->addMinutes(25)]);
        [$bidderUser, $bidderCompany] = $this->invitedCompany($rfq);
        $this->bid($rfq, $bidderCompany, $bidderUser);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertSentTo($bidderUser, CommercialStageTwoSoonNotification::class);
        $this->assertNotNull($rfq->fresh()->stage2_notified_at);
    }

    public function test_stage2_notification_is_not_sent_too_early(): void
    {
        $rfq = $this->commercialRfq(['trading_start' => now()->addHours(2)]);
        [$bidderUser, $bidderCompany] = $this->invitedCompany($rfq);
        $this->bid($rfq, $bidderCompany, $bidderUser);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertNotSentTo($bidderUser, CommercialStageTwoSoonNotification::class);
        $this->assertNull($rfq->fresh()->stage2_notified_at);
    }

    public function test_stage2_notification_skips_companies_without_offers(): void
    {
        // Приглашённый, но не подавший предложение, к торгам этапа 2 не допускается — не уведомляем.
        $rfq = $this->commercialRfq(['trading_start' => now()->addMinutes(25)]);
        [$silentUser] = $this->invitedCompany($rfq);
        [$bidderUser, $bidderCompany] = $this->invitedCompany($rfq);
        $this->bid($rfq, $bidderCompany, $bidderUser);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertSentTo($bidderUser, CommercialStageTwoSoonNotification::class);
        Notification::assertNotSentTo($silentUser, CommercialStageTwoSoonNotification::class);
    }

    public function test_stage2_notification_is_sent_only_once(): void
    {
        $rfq = $this->commercialRfq(['trading_start' => now()->addMinutes(25)]);
        [$bidderUser, $bidderCompany] = $this->invitedCompany($rfq);
        $this->bid($rfq, $bidderCompany, $bidderUser);

        $this->artisan('commercial:notify-stages')->assertSuccessful();
        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertSentToTimes($bidderUser, CommercialStageTwoSoonNotification::class, 1);
    }

    public function test_stage2_notification_is_sent_after_stage1_closed(): void
    {
        // При коротком зазоре (по умолчанию 10 минут, #222) момент «за 30 минут» наступает ещё
        // до закрытия этапа 1, но процедура может дожить до закрытия — уведомление всё равно уходит.
        $rfq = $this->commercialRfq([
            'status' => 'closed',
            'end_date' => now()->subMinutes(5),
            'trading_start' => now()->addMinutes(5),
        ]);
        [$bidderUser, $bidderCompany] = $this->invitedCompany($rfq);
        $this->bid($rfq, $bidderCompany, $bidderUser);

        $this->artisan('commercial:notify-stages')->assertSuccessful();

        Notification::assertSentTo($bidderUser, CommercialStageTwoSoonNotification::class);
    }
}
