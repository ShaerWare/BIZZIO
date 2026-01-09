<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenderInvitationSent
{
    use Dispatchable, SerializesModels;

    public Model $tender; // Может быть Rfq или Auction
    public Company $invitedCompany;
    public string $tenderType; // 'rfq' или 'auction'

    /**
     * Create a new event instance.
     */
    public function __construct(Model $tender, Company $invitedCompany, string $tenderType)
    {
        $this->tender = $tender;
        $this->invitedCompany = $invitedCompany;
        $this->tenderType = $tenderType;
    }
}