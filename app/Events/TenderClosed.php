<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenderClosed
{
    use Dispatchable, SerializesModels;

    public Model $tender; // Rfq или Auction
    public string $tenderType; // 'rfq' или 'auction'
    public ?int $winnerCompanyId;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $tender, string $tenderType, ?int $winnerCompanyId = null)
    {
        $this->tender = $tender;
        $this->tenderType = $tenderType;
        $this->winnerCompanyId = $winnerCompanyId;
    }
}