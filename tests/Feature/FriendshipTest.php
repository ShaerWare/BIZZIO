<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_friends_page_loads(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('friends.index'))
            ->assertStatus(200)
            ->assertViewIs('friends.index');
    }

    public function test_friends_search_finds_any_site_user(): void
    {
        // #142: поиск на странице друзей ищет по всем пользователям сайта,
        // а не только среди существующих друзей.
        $user = User::factory()->create(['email_verified_at' => now()]);
        $stranger = User::factory()->create(['name' => 'Иван Незнакомцев']);

        $this->actingAs($user)
            ->get(route('friends.index', ['search' => 'Незнакомцев']))
            ->assertStatus(200)
            ->assertSee('Иван Незнакомцев');
    }

    // #142 (правка): живой выпадающий поиск — JSON-эндпоинт
    public function test_142_live_search_endpoint_returns_users(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['name' => 'Иван', 'last_name' => 'Незнакомцев', 'position' => 'Прораб']);

        $response = $this->actingAs($user)->getJson(route('friends.search', ['q' => 'Незнакомцев']));

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Иван Незнакомцев'])
            ->assertJsonFragment(['subtitle' => 'Прораб']);

        // себя не возвращаем
        $self = $this->getJson(route('friends.search', ['q' => mb_substr($user->name, 0, 3)]));
        $self->assertStatus(200);
        foreach ($self->json() as $row) {
            $this->assertNotSame($user->id, $row['id']);
        }
    }

    public function test_142_live_search_requires_two_chars(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->getJson(route('friends.search', ['q' => 'и']))
            ->assertStatus(200)
            ->assertExactJson([]);
    }
}
