<?php

namespace App\Http\Requests;

class StoreDebtPaymentRequest extends DebtPaymentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('debt')) ?? false;
    }
}
