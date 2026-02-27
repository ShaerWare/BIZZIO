<?php

namespace Tests\Feature;

use App\Events\UserSubscribed;
use App\Models\Company;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubscriptionTest extends TestCase
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

    public function test_user_can_subscribe_to_another_user(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($this->user)
            ->post(route('subscriptions.users.store', $targetUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('subscriptions', [
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $targetUser->id,
        ]);
    }

    public function test_user_can_unsubscribe_from_user(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        Subscription::create([
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $targetUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('subscriptions.users.destroy', $targetUser));

        $response->assertRedirect();
        $this->assertDatabaseMissing('subscriptions', [
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $targetUser->id,
        ]);
    }

    public function test_user_can_subscribe_to_company(): void
    {
        $otherCompany = Company::factory()->create([
            'created_by' => User::factory()->create()->id,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('subscriptions.companies.store', $otherCompany));

        $response->assertRedirect();
        $this->assertDatabaseHas('subscriptions', [
            'subscriber_id' => $this->user->id,
            'subscribable_type' => Company::class,
            'subscribable_id' => $otherCompany->id,
        ]);
    }

    public function test_user_can_unsubscribe_from_company(): void
    {
        $otherCompany = Company::factory()->create([
            'created_by' => User::factory()->create()->id,
            'is_verified' => true,
        ]);

        Subscription::create([
            'subscriber_id' => $this->user->id,
            'subscribable_type' => Company::class,
            'subscribable_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('subscriptions.companies.destroy', $otherCompany));

        $response->assertRedirect();
        $this->assertDatabaseMissing('subscriptions', [
            'subscriber_id' => $this->user->id,
            'subscribable_type' => Company::class,
            'subscribable_id' => $otherCompany->id,
        ]);
    }

    public function test_user_cannot_subscribe_to_self(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('subscriptions.users.store', $this->user));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('subscriptions', [
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $this->user->id,
        ]);
    }

    public function test_double_subscription_is_idempotent(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->user)
            ->post(route('subscriptions.users.store', $targetUser));

        $this->actingAs($this->user)
            ->post(route('subscriptions.users.store', $targetUser));

        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_subscription_event_is_dispatched(): void
    {
        Event::fake([UserSubscribed::class]);

        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($this->user)
            ->post(route('subscriptions.users.store', $targetUser));

        Event::assertDispatched(UserSubscribed::class);
    }

    public function test_dashboard_shows_posts_from_subscriptions(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        // Subscribe to targetUser
        Subscription::create([
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $targetUser->id,
        ]);

        // Create post by targetUser
        $post = Post::create([
            'user_id' => $targetUser->id,
            'body' => 'Пост от подписки для теста',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Пост от подписки для теста');
    }

    public function test_dashboard_hides_posts_from_strangers(): void
    {
        $stranger = User::factory()->create(['email_verified_at' => now()]);

        // No subscription — stranger's post should not appear
        Post::create([
            'user_id' => $stranger->id,
            'body' => 'Пост от чужого пользователя',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Пост от чужого пользователя');
    }

    public function test_dashboard_shows_friends_of_friends_posts(): void
    {
        $friend = User::factory()->create(['email_verified_at' => now()]);
        $fof = User::factory()->create(['email_verified_at' => now()]);

        // User subscribes to friend
        Subscription::create([
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $friend->id,
        ]);

        // Friend subscribes to fof
        Subscription::create([
            'subscriber_id' => $friend->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $fof->id,
        ]);

        // fof creates a post
        Post::create([
            'user_id' => $fof->id,
            'body' => 'Пост от друга друга',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Пост от друга друга');
    }

    public function test_empty_feed_shows_recommended_companies(): void
    {
        // No subscriptions — should see recommendations
        $recommendedCompany = Company::factory()->create([
            'created_by' => User::factory()->create()->id,
            'is_verified' => true,
            'name' => 'Рекомендованная Компания Тест',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Подпишитесь на коллег и компании');
        $response->assertSee('Рекомендованная Компания Тест');
    }

    public function test_public_profile_is_accessible(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'Тестовый Профиль',
            'email_verified_at' => now(),
        ]);

        $response = $this->get(route('users.show', $targetUser));

        $response->assertStatus(200);
        $response->assertSee('Тестовый Профиль');
    }

    public function test_subscriptions_index_page(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Подписка Юзер']);

        Subscription::create([
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $targetUser->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('Подписка Юзер');
    }

    public function test_is_subscribed_to_helper(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $this->assertFalse($this->user->isSubscribedTo($targetUser));

        Subscription::create([
            'subscriber_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $targetUser->id,
        ]);

        $this->assertTrue($this->user->isSubscribedTo($targetUser));
    }

    public function test_guest_cannot_subscribe(): void
    {
        $targetUser = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->post(route('subscriptions.users.store', $targetUser));

        $response->assertRedirect(route('login'));
    }
}
