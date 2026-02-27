<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeRecaptchaSuccess(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);
    }

    private function fakeRecaptchaFailure(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        $this->fakeRecaptchaSuccess();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'testuser@gmail.com',
            'password' => 'password1A',
            'password_confirmation' => 'password1A',
            'g-recaptcha-response' => 'valid-token',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_fails_without_captcha(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'testuser@gmail.com',
            'password' => 'password1A',
            'password_confirmation' => 'password1A',
        ]);

        $response->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest();
    }

    public function test_registration_fails_with_invalid_captcha(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
        $this->fakeRecaptchaFailure();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'testuser@gmail.com',
            'password' => 'password1A',
            'password_confirmation' => 'password1A',
            'g-recaptcha-response' => 'invalid-token',
        ]);

        $response->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest();
    }

    public function test_registration_skips_captcha_when_secret_not_configured(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'testuser@gmail.com',
            'password' => 'password1A',
            'password_confirmation' => 'password1A',
            'g-recaptcha-response' => 'any-token',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_fails_with_short_name(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $response = $this->post('/register', [
            'name' => 'A',
            'email' => 'testuser@gmail.com',
            'password' => 'password1A',
            'password_confirmation' => 'password1A',
            'g-recaptcha-response' => 'any-token',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_registration_fails_with_weak_password(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'testuser@gmail.com',
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
            'g-recaptcha-response' => 'any-token',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
