<?php

namespace App\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TenderInvitationNotification extends Notification
{
    use Queueable;

    public Model $tender;
    public string $tenderType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Model $tender, string $tenderType)
    {
        $this->tender = $tender;
        $this->tenderType = $tenderType;
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

        return (new MailMessage)
            ->subject("Приглашение в {$tenderTypeName}: " . $this->tender->title)
            ->greeting('Здравствуйте!')
            ->line("Вашу компанию пригласили принять участие в процедуре: {$tenderTypeName}.")
            ->line('**Номер:** ' . $this->tender->number)
            ->line('**Название:** ' . $this->tender->title)
            ->line('**Организатор:** ' . $this->tender->company->name)
            ->action("Просмотреть {$tenderTypeName}", $route)
            ->line('Спасибо за использование Bizzo.ru!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tender_invitation',
            'tender_type' => $this->tenderType,
            'tender_id' => $this->tender->id,
            'tender_number' => $this->tender->number,
            'tender_title' => $this->tender->title,
            'company_name' => $this->tender->company->name,
            'url' => $this->tenderType === 'rfq' 
                ? route('rfqs.show', $this->tender) 
                : route('auctions.show', $this->tender),
        ];
    }
}