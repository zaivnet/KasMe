<?php

namespace App\Policies;

use App\Models\SavingGoalTransaction;
use App\Models\User;

class SavingGoalTransactionPolicy
{
    public function update(User $user, SavingGoalTransaction $transaction): bool
    {
        return $transaction->savingGoal->user_id === $user->id;
    }

    public function delete(User $user, SavingGoalTransaction $transaction): bool
    {
        return $transaction->savingGoal->user_id === $user->id;
    }
}
