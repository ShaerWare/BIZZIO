<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * #195 Торги этапа 2 начнутся через 30 минут.
 * Уходит пользователям, подавшим предложения на этапе 1.
 */
class CommercialStageTwoSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Rfq $rfq) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Через 30 минут начнутся торги: '.$this->rfq->title)
            ->greeting('Здравствуйте!')
            ->line('Скоро начнутся торги этапа 2 коммерческого аукциона, в котором вы подали предложение.')
            ->line('**Номер:** '.$this->rfq->number)
            ->line('**Название:** '.$this->rfq->title)
            ->line('**Начало торгов:** '.$this->rfq->trading_start->format('d.m.Y H:i').' (МСК)')
            ->action('Перейти к процедуре', $this->url())
            ->line('На этапе 2 предложение улучшается в реальном времени по трём критериям: цена, срок и аванс.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'commercial_stage2_soon',
            'rfq_id' => $this->rfq->id,
            'rfq_number' => $this->rfq->number,
            'rfq_title' => $this->rfq->title,
            'trading_start' => $this->rfq->trading_start->toIso8601String(),
            'url' => $this->url(),
        ];
    }

    /**
     * Аукцион этапа 2 к моменту отправки может быть ещё не создан (он рождается при закрытии
     * этапа 1) — тогда ведём на страницу этапа 1, она сама откроет торги при их старте (#205).
     */
    private function url(): string
    {
        return $this->rfq->linked_auction_id
            ? route('auctions.show', $this->rfq->linked_auction_id)
            : route('rfqs.show', $this->rfq);
    }
}
