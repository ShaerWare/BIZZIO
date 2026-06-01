<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialiteAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function mockProvider(string $email, string $providerAvatar): void
    {
        $social = Mockery::mock(SocialiteUser::class);
        $social->shouldReceive('getId')->andReturn('oauth-123');
        $social->shouldReceive('getEmail')->andReturn($email);
        $social->shouldReceive('getName')->andReturn('OAuth Name');
        $social->shouldReceive('getAvatar')->andReturn($providerAvatar);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($social);

        Socialite::shouldReceive('driver')->andReturn($provider);
    }

    public function test_oauth_login_keeps_existing_custom_avatar(): void
    {
        // #134: повторный вход через OAuth не должен перезатирать аватар,
        // обновлённый пользователем в bizzio.
        $user = User::factory()->create([
            'provider' => 'google',
            'provider_id' => 'oauth-123',
            'avatar' => 'avatars/custom.jpg',
        ]);

        $this->mockProvider($user->email, 'https://provider.example/avatar.png');

        $this->get(route('socialite.callback', 'google'))->assertRedirect();

        $this->assertSame('avatars/custom.jpg', $user->fresh()->avatar);
    }

    public function test_oauth_login_sets_provider_avatar_when_user_has_none(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'provider_id' => 'oauth-123',
            'avatar' => null,
        ]);

        $this->mockProvider($user->email, 'https://provider.example/avatar.png');

        $this->get(route('socialite.callback', 'google'))->assertRedirect();

        $this->assertSame('https://provider.example/avatar.png', $user->fresh()->avatar);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
