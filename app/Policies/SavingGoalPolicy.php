<?php

namespace App\Policies;

use App\Models\SavingGoal;
use App\Models\User;

class SavingGoalPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, SavingGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function update(User $user, SavingGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function delete(User $user, SavingGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }
}
