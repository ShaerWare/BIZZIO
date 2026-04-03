<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FriendRequestSent;
use App\Notifications\FriendRequestNotification;

class SendFriendRequestNotification
{
    public function handle(FriendRequestSent $event): void
    {
        $friendship = $event->friendship;
        $receiver = $friendship->receiver;

        $receiver->notify(new FriendRequestNotification($friendship));
    }
}
