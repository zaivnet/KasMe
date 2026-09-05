<?php

namespace App\Http\Requests;

class StoreSavingGoalTransactionRequest extends SavingGoalTransactionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('saving_goal')) ?? false;
    }
}
