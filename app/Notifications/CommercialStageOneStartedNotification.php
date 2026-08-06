<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * #195 Начался приём предложений (этап 1 коммерческого аукциона).
 * Уходит администраторам и модераторам приглашённых компаний в момент наступления start_date.
 */
class CommercialStageOneStartedNotification extends Notification implements ShouldQueue
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
            ->subject('Начался приём предложений: '.$this->rfq->title)
            ->greeting('Здравствуйте!')
            ->line('Открыт приём предложений на этапе 1 коммерческого аукциона, к участию в котором приглашена ваша компания.')
            ->line('**Номер:** '.$this->rfq->number)
            ->line('**Название:** '.$this->rfq->title)
            ->line('**Приём предложений до:** '.$this->rfq->end_date->format('d.m.Y H:i').' (МСК)')
            ->action('Подать предложение', route('rfqs.show', $this->rfq))
            ->line('На этапе 1 оценивается только цена. По его итогам автоматически начнутся торги этапа 2.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'commercial_stage1_started',
            'rfq_id' => $this->rfq->id,
            'rfq_number' => $this->rfq->number,
            'rfq_title' => $this->rfq->title,
            'end_date' => $this->rfq->end_date->toIso8601String(),
            'url' => route('rfqs.show', $this->rfq),
        ];
    }
}
