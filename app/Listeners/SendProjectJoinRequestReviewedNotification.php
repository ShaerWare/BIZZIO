<?php

namespace App\Listeners;

use App\Events\ProjectJoinRequestReviewed;
use App\Notifications\ProjectJoinRequestReviewedNotification;

class SendProjectJoinRequestReviewedNotification
{
    public function handle(ProjectJoinRequestReviewed $event): void
    {
        $event->joinRequest->load(['user', 'project']);

        $event->joinRequest->user->notify(
            new ProjectJoinRequestReviewedNotification($event->joinRequest, $event->decision)
        );
    }
}
