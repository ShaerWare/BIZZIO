<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TenderClosedNotification extends Notification
{
    use Queueable;

    public Model $tender;
    public string $tenderType;
    public bool $isWinner;

    /**
     * Create a new notification instance.
     */
    public function __construct(Model $tender, string $tenderType, bool $isWinner = false)
    {
        $this->tender = $tender;
        $this->tenderType = $tenderType;
        $this->isWinner = $isWinner;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenderTypeName = $this->tenderType === 'rfq' ? 'Запрос цен' : 'Аукцион';
        $route = $this->tenderType === 'rfq' 
            ? route('rfqs.show', $this->tender) 
            : route('auctions.show', $this->tender);

        $message = (new MailMessage)
            ->subject("Завершён {$tenderTypeName}: " . $this->tender->title)
            ->greeting('Здравствуйте!')
            ->line("{$tenderTypeName} завершён.")
            ->line('**Номер:** ' . $this->tender->number)
            ->line('**Название:** ' . $this->tender->title);

        if ($this->isWinner) {
            $message->line('🎉 **Поздравляем! Ваша компания определена победителем!**');
        } else {
            $message->line('К сожалению, ваша заявка не стала победителем.');
        }

        $message->action('Просмотреть результаты', $route)
                ->line('Спасибо за участие!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tender_closed',
            'tender_type' => $this->tenderType,
            'tender_id' => $this->tender->id,
            'tender_number' => $this->tender->number,
            'tender_title' => $this->tender->title,
            'is_winner' => $this->isWinner,
            'url' => $this->tenderType === 'rfq' 
                ? route('rfqs.show', $this->tender) 
                : route('auctions.show', $this->tender),
        ];
    }
}