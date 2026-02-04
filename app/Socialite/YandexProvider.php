<?php

namespace App\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class YandexProvider extends AbstractProvider
{
    protected $scopes = ['login:email', 'login:info', 'login:avatar'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://oauth.yandex.ru/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://oauth.yandex.ru/token';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get('https://login.yandex.ru/info', [
            'headers' => [
                'Authorization' => 'OAuth ' . $token,
            ],
            'query' => [
                'format' => 'json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['display_name'] ?? $user['real_name'] ?? $user['login'],
            'email' => $user['default_email'] ?? null,
            'avatar' => isset($user['default_avatar_id'])
                ? 'https://avatars.yandex.net/get-yapic/' . $user['default_avatar_id'] . '/islands-200'
                : null,
        ]);
    }
}
