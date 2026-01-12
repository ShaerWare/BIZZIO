<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('activities');
        $response->assertViewHas('unreadNotificationsCount');
    }

    public function test_guest_cannot_view_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_displays_activity_section(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        // Проверяем что страница загружается корректно
        $response->assertViewHas('activities');
    }

    public function test_user_can_load_more_activities(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard.activities', ['offset' => 0]));

        $response->assertStatus(200);
    }

    public function test_load_more_activities_with_offset(): void
    {
        // Создаём несколько записей активности
        $company = Company::factory()->create(['created_by' => $this->user->id]);

        for ($i = 0; $i < 5; $i++) {
            Project::factory()->create([
                'company_id' => $company->id,
                'name' => "Проект $i",
                'status' => 'active',
            ]);
        }

        // Первая загрузка
        $response1 = $this->actingAs($this->user)
            ->get(route('dashboard.activities', ['offset' => 0]));

        $response1->assertStatus(200);

        // Догрузка
        $response2 = $this->actingAs($this->user)
            ->get(route('dashboard.activities', ['offset' => 3]));

        $response2->assertStatus(200);
    }

    public function test_dashboard_shows_notifications_count(): void
    {
        // Создаём уведомление для пользователя
        $this->user->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\ProjectInvitationNotification',
            'data' => [
                'type' => 'project_invitation',
                'project_id' => 1,
                'project_title' => 'Тест',
                'url' => '/projects/1',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('unreadNotificationsCount', 1);
    }

    public function test_activity_log_records_company_creation(): void
    {
        $this->actingAs($this->user);

        $company = Company::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Новая компания',
        ]);

        $activity = Activity::where('subject_type', Company::class)
            ->where('subject_id', $company->id)
            ->first();

        $this->assertNotNull($activity);
        // Activity log создаёт запись (описание зависит от настройки)
        $this->assertNotEmpty($activity->description);
    }

    public function test_activity_log_records_project_creation(): void
    {
        $this->actingAs($this->user);

        $company = Company::factory()->create(['created_by' => $this->user->id]);
        $project = Project::factory()->create([
            'company_id' => $company->id,
            'name' => 'Новый проект',
            'status' => 'active',
        ]);

        $activity = Activity::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->first();

        $this->assertNotNull($activity);
        // Activity log создаёт запись
        $this->assertNotEmpty($activity->description);
    }
}
