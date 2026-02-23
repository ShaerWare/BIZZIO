<?php

namespace App\Events;

use App\Models\ProjectJoinRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectJoinRequestReviewed
{
    use Dispatchable, SerializesModels;

    public ProjectJoinRequest $joinRequest;

    public string $decision;

    public function __construct(ProjectJoinRequest $joinRequest, string $decision)
    {
        $this->joinRequest = $joinRequest;
        $this->decision = $decision;
    }
}
