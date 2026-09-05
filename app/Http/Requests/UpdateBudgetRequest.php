<?php

namespace App\Http\Requests;

class UpdateBudgetRequest extends BudgetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('budget')) ?? false;
    }
}
