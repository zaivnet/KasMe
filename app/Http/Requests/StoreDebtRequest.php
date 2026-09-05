<?php

namespace App\Http\Requests;

use App\Models\Debt;

class StoreDebtRequest extends DebtRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Debt::class) ?? false;
    }
}
