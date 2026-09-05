<?php

namespace App\Http\Requests;

use App\Models\SavingGoal;

class StoreSavingGoalRequest extends SavingGoalRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SavingGoal::class) ?? false;
    }
}
