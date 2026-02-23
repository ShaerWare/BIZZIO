<?php

namespace App\Notifications;

use App\Models\ProjectJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectJoinRequestReviewedNotification extends Notification
{
    use Queueable;

    public ProjectJoinRequest $joinRequest;

    public string $decision;

    public function __construct(ProjectJoinRequest $joinRequest, string $decision)
    {
        $this->joinRequest = $joinRequest;
        $this->decision = $decision;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->decision === 'approved' ? 'одобрен' : 'отклонён';

        $mail = (new MailMessage)
            ->subject('Ваш запрос на присоединение к проекту '.$statusText)
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Ваш запрос на присоединение к проекту **'.$this->joinRequest->project->name.'** был '.$statusText.'.');

        if ($this->joinRequest->review_comment) {
            $mail->line('Комментарий: '.$this->joinRequest->review_comment);
        }

        return $mail
            ->action('Просмотреть проект', route('projects.show', $this->joinRequest->project->slug))
            ->line('Спасибо за использование Bizzio.ru!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_join_request_reviewed',
            'project_id' => $this->joinRequest->project_id,
            'project_title' => $this->joinRequest->project->name,
            'decision' => $this->decision,
            'url' => route('projects.show', $this->joinRequest->project->slug),
        ];
    }
}
