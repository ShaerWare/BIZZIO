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
     * @param string $provider (google, vk)
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
            return redirect('/login')->withErrors(['msg' => 'Ошибка авторизации через ' . $provider]);
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
                'password' => Hash::make(Str::random(16)), // Случайный пароль
                'email_verified_at' => now(), // OAuth = уже верифицирован
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

        return redirect()->intended('/companies'); // Редирект в админку Orchid
    }

    // SocialiteController.php
    public function vkIdCallback(Request $request)
    {
        $code = $request->input('code');
        $deviceId = $request->input('device_id');

        if (!$code || !$deviceId) {
            return response()->json(['success' => false, 'error' => 'Отсутствуют параметры']);
        }

        try {
            $response = file_get_contents(
                "https://id.vk.com/oauth2/auth?" . http_build_query([
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'device_id'     => $deviceId,
                    'client_id'     => env('VK_APP_ID'),
                    'client_secret' => env('VK_SECURE_KEY'), // Secure key из VK ID кабинета
                ])
            );

            $data = json_decode($response, true);

            if (isset($data['error'])) {
                return response()->json(['success' => false, 'error' => $data['error_description']]);
            }

            // $data содержит access_token, user_id, email и т.д.
            $accessToken = $data['access_token'];
            $vkUserId = $data['user_id'];

            // Здесь можно сделать запрос к VK API для получения email и других данных
            // $vkData = file_get_contents("https://api.vk.com/method/users.get?access_token={$accessToken}&v=5.199&fields=email");

            // Создаём/находим пользователя (аналогично твоему Socialite)
            $user = User::updateOrCreate(
                ['provider' => 'vk', 'provider_id' => $vkUserId],
                [
                    'name' => $data['first_name'] . ' ' . $data['last_name'] ?? 'VK User',
                    'email' => $data['email'] ?? null,
                    'avatar' => $data['avatar'] ?? null,
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );

            Auth::login($user, true);

            return response()->json([
                'success' => true,
                'redirect' => '/dashboard' // или route('dashboard')
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}