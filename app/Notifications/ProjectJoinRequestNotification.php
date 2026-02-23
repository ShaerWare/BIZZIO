<?php

namespace App\Notifications;

use App\Models\ProjectJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectJoinRequestNotification extends Notification
{
    use Queueable;

    public ProjectJoinRequest $joinRequest;

    public function __construct(ProjectJoinRequest $joinRequest)
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
            ->subject('Запрос на присоединение к проекту: '.$this->joinRequest->project->name)
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Пользователь **'.$this->joinRequest->user->name.'** отправил запрос на присоединение к проекту **'.$this->joinRequest->project->name.'**.')
            ->when($this->joinRequest->message, function ($message) {
                return $message->line('Сообщение: '.$this->joinRequest->message);
            })
            ->action('Рассмотреть запрос', route('projects.show', $this->joinRequest->project->slug))
            ->line('Перейдите на вкладку «Люди» для одобрения или отклонения запроса.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_join_request',
            'project_id' => $this->joinRequest->project_id,
            'project_title' => $this->joinRequest->project->name,
            'user_id' => $this->joinRequest->user_id,
            'user_name' => $this->joinRequest->user->name,
            'url' => route('projects.show', $this->joinRequest->project->slug),
        ];
    }
}
