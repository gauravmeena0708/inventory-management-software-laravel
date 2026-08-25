<?php

namespace App\Policies;

use App\Models\StockTransaction;
use App\Models\User;

class StockTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, StockTransaction $transaction): bool
    {
        return $user->canViewInventory()
            && StockTransaction::visibleTo($user)->whereKey($transaction->id)->exists();
    }
}
