<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Notifications\NewCommentNotification;
use Illuminate\Support\Facades\Notification;

class SendCommentNotification
{
    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $project = $comment->project;

        // Получаем всех участников проекта (компании)
        $participantCompanies = $project->participants;

        // Собираем всех модераторов всех компаний-участников
        $moderators = collect();
        foreach ($participantCompanies as $company) {
            $moderators = $moderators->merge($company->moderators);
        }

        // Убираем автора комментария из списка получателей
        $moderators = $moderators->reject(function ($user) use ($comment) {
            return $user->id === $comment->user_id;
        });

        // Убираем дубликаты (если модератор в нескольких компаниях)
        $moderators = $moderators->unique('id');

        // Отправляем уведомления
        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new NewCommentNotification($comment));
        }
    }
}