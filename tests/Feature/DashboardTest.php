<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DashboardTest extends TestCase
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
        $this->company = Company::factory()->create(['created_by' => $this->user->id]);
        $this->company->assignModerator($this->user, 'owner');
        Storage::fake('public');
        Queue::fake();
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('activities');
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
        for ($i = 0; $i < 5; $i++) {
            Project::factory()->create([
                'company_id' => $this->company->id,
                'name' => "Проект $i",
                'status' => 'active',
            ]);
        }

        $response1 = $this->actingAs($this->user)
            ->get(route('dashboard.activities', ['offset' => 0]));
        $response1->assertStatus(200);

        $response2 = $this->actingAs($this->user)
            ->get(route('dashboard.activities', ['offset' => 3]));
        $response2->assertStatus(200);
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
        $this->assertNotEmpty($activity->description);
    }

    public function test_activity_log_records_project_creation(): void
    {
        $this->actingAs($this->user);

        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Новый проект',
            'status' => 'active',
        ]);

        $activity = Activity::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertNotEmpty($activity->description);
    }

    // === Dashboard data ===

    public function test_dashboard_has_all_view_data(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('userCompanies');
        $response->assertViewHas('pendingJoinRequests');
        $response->assertViewHas('userProjects');
        $response->assertViewHas('latestNews');
        $response->assertViewHas('recentPosts');
        $response->assertViewHas('activities');
        $response->assertViewHas('myTenders');
        $response->assertViewHas('myInvitations');
        $response->assertViewHas('myBids');
    }

    public function test_dashboard_includes_user_companies(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $companies = $response->viewData('userCompanies');
        $this->assertTrue($companies->contains('id', $this->company->id));
    }

    // === Posts CRUD ===

    public function test_user_can_create_post(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'body' => 'Тестовый пост',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'body' => 'Тестовый пост',
        ]);
    }

    public function test_user_can_create_post_with_photo(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'body' => 'Пост с фото',
                'photo' => UploadedFile::fake()->image('test.jpg', 640, 480),
            ]);

        $response->assertRedirect(route('dashboard'));
        $post = Post::where('user_id', $this->user->id)->first();
        $this->assertNotNull($post);
        $this->assertCount(1, $post->getMedia('photos'));
    }

    public function test_post_body_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'body' => '',
            ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_post_body_max_length(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('posts.store'), [
                'body' => str_repeat('а', 2001),
            ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_user_can_delete_own_post(): void
    {
        $post = Post::create([
            'user_id' => $this->user->id,
            'body' => 'Пост для удаления',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('dashboard'));
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_user_cannot_delete_others_post(): void
    {
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $post = Post::create([
            'user_id' => $otherUser->id,
            'body' => 'Чужой пост',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('posts.destroy', $post));

        $response->assertStatus(403);
    }
}
