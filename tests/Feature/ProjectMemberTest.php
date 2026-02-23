<?php

namespace Tests\Feature;

use App\Events\ProjectJoinRequestCreated;
use App\Events\ProjectJoinRequestReviewed;
use App\Events\ProjectUserInvited;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectJoinRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->company = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);
        $this->company->assignModerator($this->user, 'owner');

        $this->project = Project::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        Storage::fake('public');
        Queue::fake();
    }

    // ==========================================
    // Приглашение пользователей
    // ==========================================

    public function test_manager_can_invite_user_from_participant_company(): void
    {
        Event::fake();

        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $invitedUser = User::factory()->create();
        $participantCompany->assignModerator($invitedUser, 'moderator');

        $response = $this->actingAs($this->user)->post(
            route('projects.members.store', $this->project->slug),
            ['user_id' => $invitedUser->id, 'role' => 'member']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($this->project->fresh()->isMember($invitedUser));
        Event::assertDispatched(ProjectUserInvited::class);
    }

    public function test_cannot_invite_user_from_non_participant_company(): void
    {
        $nonParticipantCompany = Company::factory()->create(['is_verified' => true]);
        $outsideUser = User::factory()->create();
        $nonParticipantCompany->assignModerator($outsideUser, 'moderator');

        $response = $this->actingAs($this->user)->post(
            route('projects.members.store', $this->project->slug),
            ['user_id' => $outsideUser->id, 'role' => 'member']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertFalse($this->project->fresh()->isMember($outsideUser));
    }

    public function test_cannot_invite_duplicate_member(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $invitedUser = User::factory()->create();
        $participantCompany->assignModerator($invitedUser, 'moderator');

        $this->project->addMember($invitedUser, $participantCompany, 'member', $this->user);

        $response = $this->actingAs($this->user)->post(
            route('projects.members.store', $this->project->slug),
            ['user_id' => $invitedUser->id, 'role' => 'member']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_manager_cannot_invite(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(
            route('projects.members.store', $this->project->slug),
            ['user_id' => $otherUser->id, 'role' => 'member']
        );

        $response->assertStatus(403);
    }

    public function test_guest_cannot_invite(): void
    {
        $response = $this->post(
            route('projects.members.store', $this->project->slug),
            ['user_id' => 1, 'role' => 'member']
        );

        $response->assertRedirect(route('login'));
    }

    // ==========================================
    // Запросы на присоединение
    // ==========================================

    public function test_eligible_user_can_request_to_join(): void
    {
        Event::fake();

        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $eligibleUser = User::factory()->create();
        $participantCompany->assignModerator($eligibleUser, 'moderator');

        $response = $this->actingAs($eligibleUser)->post(
            route('projects.join-requests.store', $this->project->slug),
            ['message' => 'Хочу участвовать']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('project_join_requests', [
            'project_id' => $this->project->id,
            'user_id' => $eligibleUser->id,
            'status' => 'pending',
        ]);

        Event::assertDispatched(ProjectJoinRequestCreated::class);
    }

    public function test_non_participant_company_user_cannot_request_join(): void
    {
        $nonParticipantCompany = Company::factory()->create(['is_verified' => true]);
        $outsideUser = User::factory()->create();
        $nonParticipantCompany->assignModerator($outsideUser, 'moderator');

        $response = $this->actingAs($outsideUser)->post(
            route('projects.join-requests.store', $this->project->slug),
            ['message' => 'Хочу участвовать']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_already_member_cannot_request_join(): void
    {
        $this->project->addMember($this->user, $this->company, 'admin', $this->user);

        $response = $this->actingAs($this->user)->post(
            route('projects.join-requests.store', $this->project->slug),
            ['message' => 'Попытка']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_duplicate_pending_request_denied(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $eligibleUser = User::factory()->create();
        $participantCompany->assignModerator($eligibleUser, 'moderator');

        ProjectJoinRequest::create([
            'project_id' => $this->project->id,
            'user_id' => $eligibleUser->id,
            'company_id' => $participantCompany->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($eligibleUser)->post(
            route('projects.join-requests.store', $this->project->slug),
            ['message' => 'Повторный запрос']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_can_cancel_own_request(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $eligibleUser = User::factory()->create();
        $participantCompany->assignModerator($eligibleUser, 'moderator');

        $joinRequest = ProjectJoinRequest::create([
            'project_id' => $this->project->id,
            'user_id' => $eligibleUser->id,
            'company_id' => $participantCompany->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($eligibleUser)->delete(
            route('project-join-requests.destroy', $joinRequest)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('project_join_requests', ['id' => $joinRequest->id]);
    }

    // ==========================================
    // Одобрение/отклонение запросов
    // ==========================================

    public function test_manager_can_approve_join_request(): void
    {
        Event::fake();

        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $requestingUser = User::factory()->create();
        $participantCompany->assignModerator($requestingUser, 'moderator');

        $joinRequest = ProjectJoinRequest::create([
            'project_id' => $this->project->id,
            'user_id' => $requestingUser->id,
            'company_id' => $participantCompany->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->post(
            route('project-join-requests.approve', $joinRequest)
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($this->project->fresh()->isMember($requestingUser));
        $this->assertEquals('approved', $joinRequest->fresh()->status);

        Event::assertDispatched(ProjectJoinRequestReviewed::class);
    }

    public function test_manager_can_reject_join_request(): void
    {
        Event::fake();

        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $requestingUser = User::factory()->create();
        $participantCompany->assignModerator($requestingUser, 'moderator');

        $joinRequest = ProjectJoinRequest::create([
            'project_id' => $this->project->id,
            'user_id' => $requestingUser->id,
            'company_id' => $participantCompany->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->post(
            route('project-join-requests.reject', $joinRequest),
            ['review_comment' => 'Не подходит']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($this->project->fresh()->isMember($requestingUser));
        $this->assertEquals('rejected', $joinRequest->fresh()->status);
        $this->assertEquals('Не подходит', $joinRequest->fresh()->review_comment);

        Event::assertDispatched(ProjectJoinRequestReviewed::class);
    }

    public function test_non_manager_cannot_approve_request(): void
    {
        $otherUser = User::factory()->create();

        $joinRequest = ProjectJoinRequest::create([
            'project_id' => $this->project->id,
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($otherUser)->post(
            route('project-join-requests.approve', $joinRequest)
        );

        $response->assertStatus(403);
    }

    // ==========================================
    // Управление участниками
    // ==========================================

    public function test_manager_can_change_member_role(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $memberUser = User::factory()->create();
        $participantCompany->assignModerator($memberUser, 'moderator');
        $this->project->addMember($memberUser, $participantCompany, 'member', $this->user);

        $response = $this->actingAs($this->user)->put(
            route('projects.members.update', [$this->project->slug, $memberUser->id]),
            ['role' => 'moderator']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $updatedMember = $this->project->fresh()->members()->where('users.id', $memberUser->id)->first();
        $this->assertEquals('moderator', $updatedMember->pivot->role);
    }

    public function test_manager_can_remove_member(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $memberUser = User::factory()->create();
        $participantCompany->assignModerator($memberUser, 'moderator');
        $this->project->addMember($memberUser, $participantCompany, 'member', $this->user);

        $response = $this->actingAs($this->user)->delete(
            route('projects.members.destroy', [$this->project->slug, $memberUser->id])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFalse($this->project->fresh()->isMember($memberUser));
    }

    public function test_cannot_remove_project_creator(): void
    {
        $this->project->addMember($this->user, $this->company, 'admin', $this->user);

        $response = $this->actingAs($this->user)->delete(
            route('projects.members.destroy', [$this->project->slug, $this->user->id])
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue($this->project->fresh()->isMember($this->user));
    }

    // ==========================================
    // Вкладка "Люди" на странице проекта
    // ==========================================

    public function test_project_show_page_has_people_tab(): void
    {
        $response = $this->get(route('projects.show', $this->project->slug));

        $response->assertStatus(200);
        $response->assertSee('Люди');
    }

    public function test_people_tab_shows_members(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);
        $this->project->addParticipant($participantCompany, 'contractor');

        $memberUser = User::factory()->create(['name' => 'Тестовый Участник']);
        $participantCompany->assignModerator($memberUser, 'moderator');
        $this->project->addMember($memberUser, $participantCompany, 'member', $this->user);

        $response = $this->get(route('projects.show', $this->project->slug));

        $response->assertStatus(200);
        $response->assertSee('Тестовый Участник');
    }

    // ==========================================
    // Автоматическое добавление создателя
    // ==========================================

    public function test_project_creator_is_auto_added_as_admin_member(): void
    {
        $projectData = [
            'name' => 'Автопроект',
            'description' => 'Тест автодобавления',
            'company_id' => $this->company->id,
            'start_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ];

        $this->actingAs($this->user)->post(route('projects.store'), $projectData);

        $project = Project::where('name', 'Автопроект')->first();

        $this->assertNotNull($project);
        $this->assertTrue($project->isMember($this->user));

        $member = $project->members()->where('users.id', $this->user->id)->first();
        $this->assertEquals('admin', $member->pivot->role);
    }

    // ==========================================
    // Модельные методы
    // ==========================================

    public function test_user_roles_are_available(): void
    {
        $roles = Project::getUserRoles();

        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('moderator', $roles);
        $this->assertArrayHasKey('member', $roles);
    }

    public function test_is_member_returns_correct_result(): void
    {
        $memberUser = User::factory()->create();
        $nonMemberUser = User::factory()->create();

        $this->project->addMember($memberUser, $this->company, 'member');

        $this->assertTrue($this->project->isMember($memberUser));
        $this->assertFalse($this->project->isMember($nonMemberUser));
    }

    public function test_has_pending_request_from_returns_correct_result(): void
    {
        $requestingUser = User::factory()->create();

        $this->assertFalse($this->project->hasPendingRequestFrom($requestingUser));

        ProjectJoinRequest::create([
            'project_id' => $this->project->id,
            'user_id' => $requestingUser->id,
            'company_id' => $this->company->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->project->hasPendingRequestFrom($requestingUser));
    }
}
