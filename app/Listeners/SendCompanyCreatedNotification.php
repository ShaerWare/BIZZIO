<?php

namespace App\Listeners;

use App\Events\CompanyCreated;
use App\Models\User;
use App\Notifications\CompanyCreatedNotification;
use Illuminate\Support\Facades\Notification;

class SendCompanyCreatedNotification
{
    public function handle(CompanyCreated $event): void
    {
        // Отправляем уведомление всем админам (пользователи с ролью admin)
        $admins = User::whereHas('roles', function ($q) {
            $q->where('slug', 'admin');
        })->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new CompanyCreatedNotification($event->company));
        }
    }
}
