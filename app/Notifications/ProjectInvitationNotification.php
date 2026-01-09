<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProjectInvitationNotification extends Notification
{
    use Queueable;

    public Project $project;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Готовность к расширению: ['database', 'mail', 'telegram']
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Приглашение в проект: ' . $this->project->title)
            ->greeting('Здравствуйте!')
            ->line('Вашу компанию пригласили принять участие в проекте.')
            ->line('**Проект:** ' . $this->project->title)
            ->line('**Заказчик:** ' . $this->project->company->name)
            ->action('Просмотреть проект', route('projects.show', $this->project))
            ->line('Спасибо за использование Bizzo.ru!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_invitation',
            'project_id' => $this->project->id,
            'project_title' => $this->project->title,
            'company_name' => $this->project->company->name,
            'url' => route('projects.show', $this->project),
        ];
    }
}