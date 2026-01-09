<?php

namespace App\Notifications;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AuctionTradingStartedNotification extends Notification
{
    use Queueable;

    public Auction $auction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Начались торги: ' . $this->auction->title)
            ->greeting('Здравствуйте!')
            ->line('Торги в аукционе начались!')
            ->line('**Номер:** ' . $this->auction->number)
            ->line('**Название:** ' . $this->auction->title)
            ->line('**Ваш анонимный код:** ' . $this->getAnonymousCode($notifiable))
            ->action('Перейти к торгам', route('auctions.show', $this->auction))
            ->line('Удачи в торгах!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'auction_trading_started',
            'auction_id' => $this->auction->id,
            'auction_number' => $this->auction->number,
            'auction_title' => $this->auction->title,
            'anonymous_code' => $this->getAnonymousCode($notifiable),
            'url' => route('auctions.show', $this->auction),
        ];
    }

    /**
     * Получить анонимный код участника
     */
    private function getAnonymousCode(object $notifiable): ?string
    {
        // Предполагаем, что notifiable - это User
        // Ищем его заявку в аукционе через компанию
        $bid = $this->auction->bids()
            ->whereHas('company.moderators', function ($query) use ($notifiable) {
                $query->where('users.id', $notifiable->id);
            })
            ->first();

        return $bid?->anonymous_code ?? 'N/A';
    }
}