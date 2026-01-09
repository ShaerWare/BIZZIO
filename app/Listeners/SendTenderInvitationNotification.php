<?php

namespace App\Listeners;

use App\Events\TenderInvitationSent;
use App\Notifications\TenderInvitationNotification;
use Illuminate\Support\Facades\Notification;

class SendTenderInvitationNotification
{
    /**
     * Handle the event.
     */
    public function handle(TenderInvitationSent $event): void
    {
        // Получаем всех модераторов приглашённой компании
        $moderators = $event->invitedCompany->moderators;

        // Отправляем уведомление каждому модератору
        Notification::send(
            $moderators, 
            new TenderInvitationNotification($event->tender, $event->tenderType)
        );
    }
}