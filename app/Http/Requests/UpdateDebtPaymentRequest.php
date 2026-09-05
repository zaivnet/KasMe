<?php

namespace App\Http\Requests;

class UpdateDebtPaymentRequest extends DebtPaymentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('payment')) ?? false;
    }
}
