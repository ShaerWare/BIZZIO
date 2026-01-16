<?php

namespace App\Socialite;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use RuntimeException;

/**
 * VK ID OAuth2 Provider for Laravel Socialite
 *
 * Custom implementation to avoid PHP 8.4 requirement from socialiteproviders/vkid package.
 * Based on VK ID API: https://id.vk.com/about/business/go/docs/ru/vkid/latest/vk-id/intro/plan
 */
class VKIDProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     */
    protected $scopes = ['email'];

    /**
     * The separating character for the requested scopes.
     */
    protected $scopeSeparator = ' ';

    /**
     * Indicates if PKCE should be used.
     */
    protected $usesPKCE = true;

    /**
     * Get the authentication URL for the provider.
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://id.vk.com/authorize', $state);
    }

    /**
     * Get the token URL for the provider.
     */
    protected function getTokenUrl(): string
    {
        return 'https://id.vk.com/oauth2/auth';
    }

    /**
     * Get the raw user for the given access token.
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->post('https://id.vk.com/oauth2/user_info', [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => [
                'access_token' => $token,
                'client_id' => $this->clientId,
            ],
        ]);

        $contents = (string) $response->getBody();
        $response = json_decode($contents, true);

        if (! is_array($response) || ! isset($response['user'])) {
            throw new RuntimeException(sprintf(
                'Invalid JSON response from VK ID: %s',
                $contents
            ));
        }

        return $response['user'];
    }

    /**
     * Map the raw user array to a Socialite User instance.
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'user_id'),
            'name' => trim(Arr::get($user, 'first_name').' '.Arr::get($user, 'last_name')),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'avatar'),
            'nickname' => null,
        ]);
    }

    /**
     * Get the POST fields for the token request.
     */
    protected function getTokenFields($code): array
    {
        $fields = parent::getTokenFields($code);
        $fields['grant_type'] = 'authorization_code';

        // VK ID requires device_id from the callback
        if ($deviceId = request()->input('device_id')) {
            $fields['device_id'] = $deviceId;
        }

        return $fields;
    }

    /**
     * Get the access token response for the given code.
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get the code fields for authorization.
     */
    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);

        // VK ID uses 'response_type' => 'code' by default, which is correct
        return $fields;
    }
}
