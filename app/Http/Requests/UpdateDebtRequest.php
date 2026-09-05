<?php

namespace App\Http\Requests;

class UpdateDebtRequest extends DebtRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('debt')) ?? false;
    }
}
