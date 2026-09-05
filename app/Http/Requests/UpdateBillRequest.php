<?php

namespace App\Http\Requests;

class UpdateBillRequest extends BillRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('bill')) ?? false;
    }
}
