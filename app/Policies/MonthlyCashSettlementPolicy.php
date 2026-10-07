<?php

namespace App\Policies;

use App\Models\MonthlyCashSettlement;
use App\Models\User;

class MonthlyCashSettlementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MonthlyCashSettlement $settlement): bool
    {
        return $settlement->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MonthlyCashSettlement $settlement): bool
    {
        return $settlement->user_id === $user->id;
    }

    public function delete(User $user, MonthlyCashSettlement $settlement): bool
    {
        return $settlement->user_id === $user->id;
    }
}
