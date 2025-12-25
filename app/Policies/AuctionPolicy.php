<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    /**
     * Determine if the user can view any auctions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the auction.
     */
    public function view(?User $user, Auction $auction): bool
    {
        // Открытые аукционы видят все
        if ($auction->type === 'open') {
            return true;
        }
        
        // Закрытые аукционы видят только:
        // - Организатор
        // - Приглашённые компании
        // - Админы
        if (!$user) {
            return false;
        }
        
        if ($auction->canManage($user)) {
            return true;
        }
        
        // Проверка приглашения
        $userCompanies = $user->moderatedCompanies()->pluck('id');
        
        return $auction->invitations()
            ->whereIn('company_id', $userCompanies)
            ->exists();
    }

    /**
     * Determine if the user can create auctions.
     */
    public function create(User $user): bool
    {
        // Только модераторы компаний
        return $user->isModeratorOfAnyCompany();
    }

    /**
     * Determine if the user can update the auction.
     */
    public function update(User $user, Auction $auction): bool
    {
        // Только черновики можно редактировать
        return $auction->status === 'draft' && $auction->canManage($user);
    }

    /**
     * Determine if the user can delete the auction.
     */
    public function delete(User $user, Auction $auction): bool
    {
        // Только черновики можно удалить
        return $auction->status === 'draft' && $auction->canManage($user);
    }

    /**
     * Determine if the user can activate the auction.
     */
    public function activate(User $user, Auction $auction): bool
    {
        return $auction->status === 'draft' && $auction->canManage($user);
    }

    /**
     * Determine if the user can place a bid.
     */
    public function placeBid(User $user, Auction $auction): bool
    {
        // Проверка: идёт ли приём заявок или торги
        if (!$auction->isAcceptingApplications() && !$auction->isTrading()) {
            return false;
        }
        
        // Пользователь должен быть модератором хотя бы одной компании
        if (!$user->isModeratorOfAnyCompany()) {
            return false;
        }
        
        // Для закрытых процедур — проверка приглашения
        if ($auction->type === 'closed') {
            $userCompanies = $user->moderatedCompanies()->pluck('id');
            
            return $auction->invitations()
                ->whereIn('company_id', $userCompanies)
                ->exists();
        }
        
        return true;
    }
}