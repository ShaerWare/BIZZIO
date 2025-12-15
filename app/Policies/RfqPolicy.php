<?php

namespace App\Policies;

use App\Models\Rfq;
use App\Models\User;

class RfqPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Каталог доступен всем
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rfq $rfq): bool
    {
        // Публичный доступ для открытых RFQ
        if ($rfq->type === 'open') {
            return true;
        }

        // Закрытые RFQ доступны только организатору и приглашённым компаниям
        return $rfq->canManage($user) 
            || $rfq->invitations()->whereHas('company.moderators', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isModeratorOfAnyCompany();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rfq $rfq): bool
    {
        // Редактирование только до начала приёма заявок
        return $rfq->status === 'draft' && $rfq->canManage($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rfq $rfq): bool
    {
        // Удаление только до начала приёма заявок
        return $rfq->status === 'draft' && $rfq->canManage($user);
    }
}