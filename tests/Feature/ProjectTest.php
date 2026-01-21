<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectTest extends TestCase
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

        // Создаём верифицированную компанию
        $this->company = Company::factory()->create([
            'created_by' => $this->user->id,
            'is_verified' => true,
        ]);

        // Назначаем пользователя модератором
        $this->company->assignModerator($this->user, 'owner');

        Storage::fake('public');
    }

    // ==========================================
    // CRUD: Просмотр списка и карточки проекта
    // ==========================================

    public function test_guest_can_view_projects_list(): void
    {
        Project::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.index');
    }

    public function test_guest_can_view_project_page(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $response = $this->get(route('projects.show', $project->slug));

        $response->assertStatus(200);
        $response->assertViewIs('projects.show');
        $response->assertSee($project->name);
    }

    public function test_projects_list_can_be_filtered_by_status(): void
    {
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
        ]);

        $response = $this->get(route('projects.index', ['status' => 'active']));

        $response->assertStatus(200);
    }

    public function test_projects_list_can_be_searched(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Строительство ЖК Солнечный',
            'status' => 'active',
        ]);

        $response = $this->get(route('projects.index', ['search' => 'Солнечный']));

        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    // ==========================================
    // CRUD: Создание проекта
    // ==========================================

    public function test_guest_cannot_create_project(): void
    {
        $response = $this->get(route('projects.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_company_cannot_create_project(): void
    {
        $userWithoutCompany = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($userWithoutCompany)
            ->get(route('projects.create'));

        // Должен редиректить с ошибкой
        $response->assertRedirect(route('projects.index'));
    }

    public function test_company_moderator_can_view_create_project_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('projects.create'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.create');
    }

    public function test_company_moderator_can_create_project(): void
    {
        $projectData = [
            'name' => 'Новый проект',
            'description' => 'Краткое описание проекта',
            'full_description' => 'Полное описание проекта для тестирования',
            'company_id' => $this->company->id,
            'start_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('projects.store'), $projectData);

        $response->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'Новый проект',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_project_slug_is_auto_generated(): void
    {
        $projectData = [
            'name' => 'Тестовый Проект',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'start_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ];

        $this->actingAs($this->user)
            ->post(route('projects.store'), $projectData);

        $project = Project::where('name', 'Тестовый Проект')->first();

        $this->assertNotNull($project->slug);
        $this->assertStringContainsString('testovyi-proekt', $project->slug);
    }

    public function test_project_can_be_created_with_participants(): void
    {
        $participantCompany = Company::factory()->create(['is_verified' => true]);

        $projectData = [
            'name' => 'Проект с участниками',
            'description' => 'Описание',
            'company_id' => $this->company->id,
            'start_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'participants' => [
                [
                    'company_id' => $participantCompany->id,
                    'role' => 'contractor',
                    'participation_description' => 'Основной подрядчик',
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('projects.store'), $projectData);

        $project = Project::where('name', 'Проект с участниками')->first();

        $this->assertCount(1, $project->participants);
        $this->assertTrue($project->participants->contains($participantCompany));
    }

    // ==========================================
    // CRUD: Редактирование проекта
    // ==========================================

    public function test_company_moderator_can_edit_own_project(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.edit', $project->slug));

        $response->assertStatus(200);
        $response->assertViewIs('projects.edit');
    }

    public function test_non_moderator_cannot_edit_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('projects.edit', $project->slug));

        $response->assertStatus(403);
    }

    public function test_company_moderator_can_update_project(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Старое название',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('projects.update', $project->slug), [
                'name' => 'Новое название',
                'description' => 'Обновленное описание',
                'company_id' => $this->company->id,
                'start_date' => now()->format('Y-m-d'),
                'status' => 'active',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Новое название',
        ]);
    }

    // ==========================================
    // CRUD: Удаление проекта
    // ==========================================

    public function test_project_creator_can_delete_project(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.destroy', $project->slug));

        $response->assertRedirect(route('projects.index'));
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_non_creator_cannot_delete_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('projects.destroy', $project->slug));

        $response->assertStatus(403);
    }

    // ==========================================
    // Участники проекта
    // ==========================================

    public function test_project_can_have_multiple_participants(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $participant1 = Company::factory()->create(['is_verified' => true]);
        $participant2 = Company::factory()->create(['is_verified' => true]);

        $project->addParticipant($participant1, 'contractor', 'Подрядчик');
        $project->addParticipant($participant2, 'supplier', 'Поставщик');

        $this->assertCount(2, $project->participants);
    }

    public function test_participant_can_be_removed(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $participant = Company::factory()->create(['is_verified' => true]);
        $project->addParticipant($participant, 'contractor');

        $this->assertCount(1, $project->participants);

        $project->removeParticipant($participant);

        $this->assertCount(0, $project->fresh()->participants);
    }

    // ==========================================
    // Комментарии к проектам
    // ==========================================

    public function test_authenticated_user_can_add_comment(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('projects.comments.store', $project->slug), [
                'body' => 'Это тестовый комментарий',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'body' => 'Это тестовый комментарий',
        ]);
    }

    public function test_guest_cannot_add_comment(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post(route('projects.comments.store', $project->slug), [
            'body' => 'Комментарий от гостя',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_reply_to_comment(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $parentComment = Comment::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'body' => 'Родительский комментарий',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->post(route('projects.comments.store', $project->slug), [
                'body' => 'Ответ на комментарий',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'parent_id' => $parentComment->id,
        ]);
    }

    public function test_comment_author_can_edit_comment(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $comment = Comment::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'body' => 'Исходный текст',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('comments.update', $comment), [
                'body' => 'Обновленный текст',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_comments', [
            'id' => $comment->id,
            'body' => 'Обновленный текст',
        ]);
    }

    public function test_other_user_cannot_edit_comment(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $comment = Comment::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'body' => 'Комментарий',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->put(route('comments.update', $comment), [
                'body' => 'Попытка редактирования',
            ]);

        $response->assertStatus(403);
    }

    public function test_comment_author_can_delete_comment(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $comment = Comment::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'body' => 'Комментарий для удаления',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $this->assertSoftDeleted('project_comments', ['id' => $comment->id]);
    }

    // ==========================================
    // Статусы проектов
    // ==========================================

    public function test_project_statuses_are_available(): void
    {
        $statuses = Project::getStatuses();

        $this->assertArrayHasKey('active', $statuses);
        $this->assertArrayHasKey('completed', $statuses);
        $this->assertArrayHasKey('cancelled', $statuses);
    }

    public function test_active_scope_returns_only_active_projects(): void
    {
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
        ]);

        $activeProjects = Project::active()->get();

        $this->assertCount(1, $activeProjects);
        $this->assertEquals('active', $activeProjects->first()->status);
    }

    // ==========================================
    // Роли участников
    // ==========================================

    public function test_participant_roles_are_available(): void
    {
        $roles = Project::getParticipantRoles();

        $this->assertArrayHasKey('customer', $roles);
        $this->assertArrayHasKey('general_contractor', $roles);
        $this->assertArrayHasKey('contractor', $roles);
        $this->assertArrayHasKey('supplier', $roles);
        $this->assertArrayHasKey('consultant', $roles);
    }

    // ==========================================
    // Права доступа
    // ==========================================

    public function test_company_moderator_can_manage_project(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertTrue($project->canManage($this->user));
    }

    public function test_non_moderator_cannot_manage_project(): void
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $otherUser = User::factory()->create();

        $this->assertFalse($project->canManage($otherUser));
    }
}
