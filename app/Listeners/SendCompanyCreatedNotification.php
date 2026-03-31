<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CompanyCreated;
use App\Models\User;
use App\Notifications\CompanyCreatedNotification;
use Illuminate\Support\Facades\Notification;

class SendCompanyCreatedNotification
{
    public function handle(CompanyCreated $event): void
    {
        $notification = new CompanyCreatedNotification($event->company);

        // Отправляем уведомление всем админам (пользователи с ролью admin) — database + mail
        $admins = User::whereHas('roles', function ($q) {
            $q->where('slug', 'admin');
        })->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }

        // Гарантированная отправка на admin email (даже если нет admin-пользователей в системе)
        $adminEmail = config('app.admin_email');
        if ($adminEmail) {
            $alreadySent = $admins->contains(fn (User $u) => $u->email === $adminEmail);
            if (! $alreadySent) {
                Notification::route('mail', $adminEmail)->notify($notification);
            }
        }
    }
}
