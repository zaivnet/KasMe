<?php

namespace App\Http\Requests;

class UpdateSavingGoalTransactionRequest extends SavingGoalTransactionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('transaction')) ?? false;
    }
}
