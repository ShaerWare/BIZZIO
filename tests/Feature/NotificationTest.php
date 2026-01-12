<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Notifications\ProjectInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_notifications_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_get_notifications_as_json(): void
    {
        // Создаём тестовое уведомление
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $project = Project::factory()->create(['company_id' => $company->id]);

        $this->user->notify(new ProjectInvitationNotification($project));

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'notifications',
            'hasMore',
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        // Создаём тестовое уведомление
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $project = Project::factory()->create(['company_id' => $company->id]);

        $this->user->notify(new ProjectInvitationNotification($project));

        $notification = $this->user->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $notification->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        // Создаём несколько уведомлений
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $project1 = Project::factory()->create(['company_id' => $company->id]);
        $project2 = Project::factory()->create(['company_id' => $company->id]);

        $this->user->notify(new ProjectInvitationNotification($project1));
        $this->user->notify(new ProjectInvitationNotification($project2));

        $this->assertEquals(2, $this->user->unreadNotifications()->count());

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read-all'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unreadCount' => 0,
        ]);

        $this->assertEquals(0, $this->user->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_get_unread_notifications_count(): void
    {
        // Создаём тестовое уведомление
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $project = Project::factory()->create(['company_id' => $company->id]);

        $this->user->notify(new ProjectInvitationNotification($project));

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertStatus(200);
        $response->assertJson([
            'count' => 1,
        ]);
    }

    public function test_notification_contains_correct_data(): void
    {
        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $project = Project::factory()->create([
            'company_id' => $company->id,
            'name' => 'Тестовый проект',
        ]);

        $this->user->notify(new ProjectInvitationNotification($project));

        $notification = $this->user->notifications()->first();

        $this->assertEquals('project_invitation', $notification->data['type']);
        $this->assertEquals($project->id, $notification->data['project_id']);
        $this->assertEquals('Тестовый проект', $notification->data['project_title']);
    }
}
