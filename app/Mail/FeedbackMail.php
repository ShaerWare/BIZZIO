<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * #286 — Письмо с формы обратной связи в профиле пользователя.
 */
class FeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $sender,
        public string $senderName,
        public ?string $senderCompany,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Обратная связь: '.mb_substr($this->body, 0, 50),
            replyTo: [new Address($this->sender->email, $this->senderName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.feedback',
        );
    }
}
