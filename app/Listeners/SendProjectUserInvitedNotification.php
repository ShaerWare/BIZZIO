<?php

namespace App\Listeners;

use App\Events\ProjectUserInvited;
use App\Notifications\ProjectUserInvitedNotification;

class SendProjectUserInvitedNotification
{
    public function handle(ProjectUserInvited $event): void
    {
        $event->invitedUser->notify(
            new ProjectUserInvitedNotification($event->project, $event->invitedBy)
        );
    }
}
