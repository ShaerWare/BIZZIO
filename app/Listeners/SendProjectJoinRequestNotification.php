<?php

namespace App\Listeners;

use App\Events\ProjectJoinRequestCreated;
use App\Notifications\ProjectJoinRequestNotification;

class SendProjectJoinRequestNotification
{
    public function handle(ProjectJoinRequestCreated $event): void
    {
        $event->joinRequest->load(['user', 'project.company']);

        // Уведомляем модераторов компании-владельца проекта
        $moderators = $event->joinRequest->project->company->moderators;

        foreach ($moderators as $moderator) {
            $moderator->notify(new ProjectJoinRequestNotification($event->joinRequest));
        }
    }
}
