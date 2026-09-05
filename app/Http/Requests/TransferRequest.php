<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        $ownedAccount = fn () => Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'));

        return [
            'from_account_id' => ['required', 'integer', 'different:to_account_id', $ownedAccount()],
            'to_account_id' => ['required', 'integer', 'different:from_account_id', $ownedAccount()],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'fee' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999999.99'],
            'transfer_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
