<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * #181 Новая главная: единая точка входа, гостевой и авторизованный варианты.
 */
class HomeV26Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    private function member(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::factory()->create(['created_by' => $user->id]);
        $company->assignModerator($user, 'owner');

        return $user;
    }

    public function test_guest_sees_guest_home(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home.guest');
        $response->assertSee('Добро пожаловать в Bizzio!');
    }

    public function test_authenticated_user_sees_authorized_home(): void
    {
        $response = $this->actingAs($this->member())->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home.authorized');
        $response->assertSee('Мои закупки');
    }

    public function test_guest_home_offers_login_and_register(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(config('app.auth_url'), false);
        $response->assertSee(config('app.register_url'), false);
    }

    public function test_future_services_are_marked_for_analytics(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('data-future-service="ai_assistant"', false);
        $response->assertSee('data-service-name="Покупка-продажа бизнеса"', false);
    }

    public function test_inactive_features_are_marked_for_analytics(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('data-inactive-feature="suggest_service"', false);
    }

    public function test_authorized_home_shows_feed_posts(): void
    {
        $user = $this->member();
        \App\Models\Post::create(['user_id' => $user->id, 'body' => 'Пост для новой главной']);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Пост для новой главной');
    }
}
