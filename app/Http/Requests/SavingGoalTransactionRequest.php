<?php

namespace App\Http\Requests;

use App\Models\SavingGoalTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SavingGoalTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'))],
            'type' => ['required', Rule::in(array_keys(SavingGoalTransaction::TYPES))],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
