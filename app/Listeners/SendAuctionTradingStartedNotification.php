<?php

namespace App\Listeners;

use App\Events\AuctionTradingStarted;
use App\Notifications\AuctionTradingStartedNotification;
use Illuminate\Support\Facades\Notification;

class SendAuctionTradingStartedNotification
{
    /**
     * Handle the event.
     */
    public function handle(AuctionTradingStarted $event): void
    {
        $auction = $event->auction;

        // Получаем все начальные заявки (type = 'initial')
        $initialBids = $auction->bids()
            ->where('type', 'initial')
            ->with('company.moderators')
            ->get();

        // Собираем всех модераторов компаний-участников
        $moderators = collect();
        foreach ($initialBids as $bid) {
            $moderators = $moderators->merge($bid->company->moderators);
        }

        // Убираем дубликаты
        $moderators = $moderators->unique('id');

        // Отправляем уведомления
        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new AuctionTradingStartedNotification($auction));
        }
    }
}