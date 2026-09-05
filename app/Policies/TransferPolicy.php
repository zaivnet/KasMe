<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $transfer->user_id === $user->id;
    }

    public function update(User $user, Transfer $transfer): bool
    {
        return $transfer->user_id === $user->id;
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        return $transfer->user_id === $user->id;
    }
}
