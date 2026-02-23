<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectJoinRequest extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'company_id',
        'desired_role',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function canCancel(User $user): bool
    {
        return $this->user_id === $user->id && $this->status === 'pending';
    }

    public function canReview(User $user): bool
    {
        if (! $this->relationLoaded('project')) {
            $this->load('project');
        }

        return $this->project && $this->project->canManage($user) && $this->status === 'pending';
    }
}
