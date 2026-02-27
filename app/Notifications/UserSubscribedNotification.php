<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserSubscribedNotification extends Notification
{
    use Queueable;

    public Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $subscriber = $this->subscription->subscriber;

        return [
            'type' => 'user_subscribed',
            'subscriber_id' => $subscriber->id,
            'subscriber_name' => $subscriber->name,
            'url' => route('users.show', $subscriber),
        ];
    }
}
