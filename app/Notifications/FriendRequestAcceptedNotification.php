<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Friendship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FriendRequestAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Friendship $friendship;

    public function __construct(Friendship $friendship)
    {
        $this->friendship = $friendship;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $accepter = $this->friendship->receiver;

        return [
            'type' => 'friend_request_accepted',
            'message' => $accepter->name.' принял(а) вашу заявку в друзья',
            'accepter_id' => $accepter->id,
            'accepter_name' => $accepter->name,
            'url' => route('users.show', $accepter),
        ];
    }
}
