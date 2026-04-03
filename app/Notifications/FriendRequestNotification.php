<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Friendship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification implements ShouldQueue
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
        $sender = $this->friendship->sender;

        return [
            'type' => 'friend_request',
            'message' => $sender->name.' хочет добавить вас в друзья',
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'url' => route('friends.index', ['tab' => 'incoming']),
        ];
    }
}
