<?php

namespace App\Listeners;

use App\Events\TenderClosed;
use App\Notifications\TenderClosedNotification;
use Illuminate\Support\Facades\Notification;

class SendTenderClosedNotification
{
    /**
     * Handle the event.
     */
    public function handle(TenderClosed $event): void
    {
        $tender = $event->tender;
        $tenderType = $event->tenderType;
        $winnerCompanyId = $event->winnerCompanyId;

        // Получаем все заявки
        $bids = $tender->bids()->with('company.moderators')->get();

        foreach ($bids as $bid) {
            $isWinner = $bid->company_id === $winnerCompanyId;
            $moderators = $bid->company->moderators;

            // Отправляем уведомление модераторам компании
            Notification::send(
                $moderators,
                new TenderClosedNotification($tender, $tenderType, $isWinner)
            );
        }
    }
}