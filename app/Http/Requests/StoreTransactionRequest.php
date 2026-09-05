<?php

namespace App\Http\Requests;

use App\Models\Transaction;

class StoreTransactionRequest extends TransactionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
    }
}
