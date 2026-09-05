<?php

namespace App\Http\Requests;

use App\Models\Budget;

class StoreBudgetRequest extends BudgetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Budget::class) ?? false;
    }
}
