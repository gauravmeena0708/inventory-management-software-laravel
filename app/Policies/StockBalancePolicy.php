<?php

namespace App\Policies;

use App\Models\StockBalance;
use App\Models\User;

class StockBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, StockBalance $balance): bool
    {
        return $user->canViewInventory()
            && StockBalance::visibleTo($user)->whereKey($balance->id)->exists();
    }
}
