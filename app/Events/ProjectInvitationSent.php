<?php

namespace App\Events;

use App\Models\Project;
use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectInvitationSent
{
    use Dispatchable, SerializesModels;

    public Project $project;
    public Company $invitedCompany;

    /**
     * Create a new event instance.
     */
    public function __construct(Project $project, Company $invitedCompany)
    {
        $this->project = $project;
        $this->invitedCompany = $invitedCompany;
    }
}