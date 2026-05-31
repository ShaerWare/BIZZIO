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
}
