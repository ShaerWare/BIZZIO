<?php

namespace App\Listeners;

use App\Events\ProjectInvitationSent;
use App\Notifications\ProjectInvitationNotification;
use Illuminate\Support\Facades\Notification;

class SendProjectInvitationNotification
{
    /**
     * Handle the event.
     */
    public function handle(ProjectInvitationSent $event): void
    {
        // Получаем всех модераторов приглашённой компании
        $moderators = $event->invitedCompany->moderators;

        // Отправляем уведомление каждому модератору
        Notification::send($moderators, new ProjectInvitationNotification($event->project));
    }
}