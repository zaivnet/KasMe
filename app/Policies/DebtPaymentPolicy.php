<?php

namespace App\Policies;

use App\Models\DebtPayment;
use App\Models\User;

class DebtPaymentPolicy
{
    public function update(User $user, DebtPayment $payment): bool
    {
        return $payment->debt->user_id === $user->id;
    }

    public function delete(User $user, DebtPayment $payment): bool
    {
        return $payment->debt->user_id === $user->id;
    }
}
