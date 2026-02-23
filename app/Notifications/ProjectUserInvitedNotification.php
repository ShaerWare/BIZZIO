<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectUserInvitedNotification extends Notification
{
    use Queueable;

    public Project $project;

    public User $invitedBy;

    public function __construct(Project $project, User $invitedBy)
    {
        $this->project = $project;
        $this->invitedBy = $invitedBy;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Вас добавили в проект: '.$this->project->name)
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Пользователь **'.$this->invitedBy->name.'** добавил вас в проект **'.$this->project->name.'**.')
            ->action('Просмотреть проект', route('projects.show', $this->project->slug))
            ->line('Спасибо за использование Bizzio.ru!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_user_invited',
            'project_id' => $this->project->id,
            'project_title' => $this->project->name,
            'invited_by_name' => $this->invitedBy->name,
            'url' => route('projects.show', $this->project->slug),
        ];
    }
}
