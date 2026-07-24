<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBadgeDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_is_shown_on_public_profile(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        UserBadge::create([
            'user_id' => $user->id,
            'color' => '#7b1e1e',
            'label' => 'Подтверждён',
        ]);

        $response = $this->actingAs($user)->get(route('users.show', $user));

        $response->assertOk();
        $response->assertSee('Подтверждён');
        $response->assertSee('#7b1e1e');
    }

    public function test_badge_without_label_still_renders_border_but_no_chip(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        UserBadge::create([
            'user_id' => $user->id,
            'color' => '#28a745',
            'label' => '',
        ]);

        $response = $this->actingAs($user)->get(route('users.show', $user));

        $response->assertOk();
        // Рамка контейнера использует цвет бейджа даже без подписи.
        $response->assertSee('#28a745');
    }

    public function test_user_has_multiple_badges(): void
    {
        $user = User::factory()->create();
        UserBadge::create(['user_id' => $user->id, 'color' => '#dc3545', 'label' => 'Подозрительная личность']);
        UserBadge::create(['user_id' => $user->id, 'color' => '#28a745', 'label' => 'Подтверждён']);

        $this->assertSame(2, $user->badges()->count());
    }
}
