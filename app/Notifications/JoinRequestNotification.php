<?php

namespace App\Notifications;

use App\Models\CompanyJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class JoinRequestNotification extends Notification
{
    use Queueable;

    public CompanyJoinRequest $joinRequest;

    public function __construct(CompanyJoinRequest $joinRequest)
    {
        $this->joinRequest = $joinRequest;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Запрос на присоединение к компании ' . $this->joinRequest->company->name)
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Пользователь **' . $this->joinRequest->user->name . '** отправил запрос на присоединение к компании **' . $this->joinRequest->company->name . '**.')
            ->when($this->joinRequest->desired_role, function ($message) {
                return $message->line('Желаемая роль: ' . $this->joinRequest->desired_role);
            })
            ->when($this->joinRequest->message, function ($message) {
                return $message->line('Сообщение: ' . $this->joinRequest->message);
            })
            ->action('Рассмотреть запрос', route('companies.show', $this->joinRequest->company))
            ->line('Перейдите на вкладку «Люди» для одобрения или отклонения запроса.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'join_request',
            'company_id' => $this->joinRequest->company_id,
            'company_name' => $this->joinRequest->company->name,
            'user_id' => $this->joinRequest->user_id,
            'user_name' => $this->joinRequest->user->name,
            'url' => route('companies.show', $this->joinRequest->company),
        ];
    }
}
