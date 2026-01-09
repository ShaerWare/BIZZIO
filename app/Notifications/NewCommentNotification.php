<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewCommentNotification extends Notification
{
    use Queueable;

    public Comment $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
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
        return (new MailMessage)
            ->subject('Новый комментарий в проекте: ' . $this->comment->project->title)
            ->greeting('Здравствуйте!')
            ->line('Новый комментарий в проекте, где участвует ваша компания.')
            ->line('**Проект:** ' . $this->comment->project->title)
            ->line('**Автор:** ' . $this->comment->user->name)
            ->line('**Комментарий:** ' . \Illuminate\Support\Str::limit($this->comment->content, 100))
            ->action('Просмотреть', route('projects.show', $this->comment->project))
            ->line('Спасибо за использование Bizzo.ru!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_comment',
            'comment_id' => $this->comment->id,
            'project_id' => $this->comment->project_id,
            'project_title' => $this->comment->project->title,
            'author_name' => $this->comment->user->name,
            'preview' => \Illuminate\Support\Str::limit($this->comment->content, 100),
            'url' => route('projects.show', $this->comment->project) . '#comment-' . $this->comment->id,
        ];
    }
}