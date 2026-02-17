<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyCreated
{
    use Dispatchable, SerializesModels;

    public Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }
}
