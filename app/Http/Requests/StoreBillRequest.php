<?php

namespace App\Http\Requests;

use App\Models\Bill;

class StoreBillRequest extends BillRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Bill::class) ?? false;
    }
}
