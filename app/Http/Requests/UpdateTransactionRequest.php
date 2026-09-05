<?php

namespace App\Http\Requests;

class UpdateTransactionRequest extends TransactionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('transaction')) ?? false;
    }
}
