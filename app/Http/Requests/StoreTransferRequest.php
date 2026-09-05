<?php

namespace App\Http\Requests;

use App\Models\Transfer;

class StoreTransferRequest extends TransferRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transfer::class) ?? false;
    }
}
