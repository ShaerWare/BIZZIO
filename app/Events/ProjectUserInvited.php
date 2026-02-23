<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectUserInvited
{
    use Dispatchable, SerializesModels;

    public Project $project;

    public User $invitedUser;

    public User $invitedBy;

    public function __construct(Project $project, User $invitedUser, User $invitedBy)
    {
        $this->project = $project;
        $this->invitedUser = $invitedUser;
        $this->invitedBy = $invitedBy;
    }
}
