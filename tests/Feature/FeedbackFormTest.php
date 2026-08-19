<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\FeedbackMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * #286 — Форма обратной связи: письмо уходит на ADMIN_NOTIFICATION_EMAIL,
 * поддерживается несколько получателей, сбой отправки не теряется молча.
 */
class FeedbackFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_is_sent_to_admin_email(): void
    {
        Mail::fake();
        config(['app.admin_email' => 'admin@bizzio.ru']);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post('/profile/feedback', [
            'name' => 'Иван Иванов',
            'company' => 'ООО Ромашка',
            'message' => 'Не работает кнопка подачи заявки.',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'feedback-sent');

        Mail::assertSent(function (FeedbackMail $mail) use ($user) {
            return $mail->hasTo('admin@bizzio.ru')
                && $mail->hasReplyTo($user->email)
                && $mail->senderCompany === 'ООО Ромашка';
        });
    }

    public function test_feedback_is_sent_to_all_configured_recipients(): void
    {
        Mail::fake();
        config(['app.admin_email' => 'admin@bizzio.ru, support@bizzio.ru']);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/profile/feedback', [
            'name' => 'Иван Иванов',
            'message' => 'Тестовое сообщение.',
        ])->assertSessionHas('status', 'feedback-sent');

        Mail::assertSent(function (FeedbackMail $mail) {
            return $mail->hasTo('admin@bizzio.ru') && $mail->hasTo('support@bizzio.ru');
        });
    }

    public function test_feedback_reports_failure_when_recipient_is_not_configured(): void
    {
        Mail::fake();
        config(['app.admin_email' => '']);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/profile/feedback', [
            'name' => 'Иван Иванов',
            'message' => 'Тестовое сообщение.',
        ])->assertSessionHas('status', 'feedback-failed');

        Mail::assertNothingSent();
    }

    public function test_feedback_requires_name_and_message(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post('/profile/feedback', ['name' => '', 'message' => ''])
            ->assertSessionHasErrors(['name', 'message']);

        Mail::assertNothingSent();
    }

    public function test_guest_cannot_send_feedback(): void
    {
        Mail::fake();

        $this->post('/profile/feedback', [
            'name' => 'Гость',
            'message' => 'Сообщение.',
        ])->assertRedirect(route('login'));

        Mail::assertNothingSent();
    }
}
