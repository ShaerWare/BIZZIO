<?php

namespace App\Events;

use App\Models\ProjectJoinRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectJoinRequestCreated
{
    use Dispatchable, SerializesModels;

    public ProjectJoinRequest $joinRequest;

    public function __construct(ProjectJoinRequest $joinRequest)
    {
        $this->joinRequest = $joinRequest;
    }
}
