<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FriendRequestAccepted;
use App\Notifications\FriendRequestAcceptedNotification;

class SendFriendRequestAcceptedNotification
{
    public function handle(FriendRequestAccepted $event): void
    {
        $friendship = $event->friendship;
        $sender = $friendship->sender;

        $sender->notify(new FriendRequestAcceptedNotification($friendship));
    }
}
