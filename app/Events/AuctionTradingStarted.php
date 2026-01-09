<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionTradingStarted
{
    use Dispatchable, SerializesModels;

    public Auction $auction;

    /**
     * Create a new event instance.
     */
    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }
}