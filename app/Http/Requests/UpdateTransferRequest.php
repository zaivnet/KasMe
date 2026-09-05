<?php

namespace App\Http\Requests;

class UpdateTransferRequest extends TransferRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('transfer')) ?? false;
    }
}
