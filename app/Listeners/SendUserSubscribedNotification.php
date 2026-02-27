<?php

namespace App\Listeners;

use App\Events\UserSubscribed;
use App\Models\User;
use App\Notifications\UserSubscribedNotification;

class SendUserSubscribedNotification
{
    public function handle(UserSubscribed $event): void
    {
        $subscription = $event->subscription;
        $target = $subscription->subscribable;

        // Уведомляем только пользователей (не компании)
        if (! $target instanceof User) {
            return;
        }

        // Не уведомлять самого себя
        if ($target->id === $subscription->subscriber_id) {
            return;
        }

        $target->notify(new UserSubscribedNotification($subscription));
    }
}
