<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to OAuth provider
     *
     * @param string $provider (google, yandex)
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback
     *
     * @param string $provider
     */
    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            \Log::error("OAuth callback error for {$provider}: " . $e->getMessage());
            return redirect('/login')->withErrors(['msg' => 'Ошибка авторизации через ' . $provider . ': ' . $e->getMessage()]);
        }

        // Ищем пользователя по provider + provider_id
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // Если не нашли, ищем по email
        if (!$user) {
            $user = User::where('email', $socialUser->getEmail())->first();
        }

        // Если всё ещё нет — создаём нового
        if (!$user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
            ]);

            // Назначаем роль Subscriber по умолчанию
            $role = \Orchid\Platform\Models\Role::where('slug', 'subscriber')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        } else {
            // Обновляем данные OAuth (если пользователь уже был)
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        // Входим в систему
        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
