<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #268 Организатор приглашает зарегистрированную компанию во время этапа 1
 * (до окончания приёма заявок), а не только пока процедура — черновик.
 */
class InviteDuringStageOneTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Company $organizerCompany;

    private Company $guest;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        $this->organizer = User::factory()->create(['email_verified_at' => now()]);
        $this->organizerCompany = Company::factory()->create([
            'created_by' => $this->organizer->id,
            'is_verified' => true,
        ]);
        $this->organizerCompany->assignModerator($this->organizer, 'owner');

        $guestOwner = User::factory()->create(['email_verified_at' => now()]);
        $this->guest = Company::factory()->create([
            'name' => 'Приглашаемая компания',
            'created_by' => $guestOwner->id,
            'is_verified' => true,
        ]);
        $this->guest->assignModerator($guestOwner, 'owner');
    }

    private function createRfq(array $overrides = []): Rfq
    {
        return Rfq::create(array_merge([
            'number' => Rfq::generateNumber(),
            'title' => 'Коммерческий аукцион',
            'company_id' => $this->organizerCompany->id,
            'created_by' => $this->organizer->id,
            'type' => 'closed',
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
        ], $overrides));
    }

    public function test_organizer_can_invite_company_while_stage_one_is_running(): void
    {
        $rfq = $this->createRfq();

        $this->assertTrue($rfq->canInviteCompanies($this->organizer));

        $this->actingAs($this->organizer)
            ->postJson(route('rfqs.invitations.store', $rfq), ['company_id' => $this->guest->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('rfq_invitations', [
            'rfq_id' => $rfq->id,
            'company_id' => $this->guest->id,
            'status' => 'pending',
        ]);
    }

    public function test_invite_block_is_visible_on_active_procedure(): void
    {
        $rfq = $this->createRfq();

        $this->actingAs($this->organizer)
            ->get(route('rfqs.show', $rfq))
            ->assertOk()
            ->assertSee('Пригласить компании');
    }

    public function test_invite_is_closed_after_stage_one_ends(): void
    {
        $rfq = $this->createRfq(['status' => 'closed']);

        $this->assertFalse($rfq->canInviteCompanies($this->organizer));

        $this->actingAs($this->organizer)
            ->postJson(route('rfqs.invitations.store', $rfq), ['company_id' => $this->guest->id])
            ->assertStatus(422);
    }

    public function test_expired_procedure_no_longer_accepts_invitations(): void
    {
        $rfq = $this->createRfq([
            'start_date' => now()->subDays(3),
            'end_date' => now()->subHour(),
        ]);

        $this->assertFalse($rfq->canInviteCompanies($this->organizer));
    }

    public function test_non_organizer_cannot_invite(): void
    {
        $rfq = $this->createRfq();
        $stranger = User::factory()->create(['email_verified_at' => now()]);

        $this->assertFalse($rfq->canInviteCompanies($stranger));

        $this->actingAs($stranger)
            ->postJson(route('rfqs.invitations.store', $rfq), ['company_id' => $this->guest->id])
            ->assertForbidden();
    }
}
