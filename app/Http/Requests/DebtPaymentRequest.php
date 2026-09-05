<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class DebtPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where(fn ($query) => $query
                ->where('user_id', $this->user()->id)->whereNull('deleted_at'))],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
