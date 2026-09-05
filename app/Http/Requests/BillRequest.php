<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BillRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'due_date' => ['required', 'date'],
            'recurrence' => ['required', Rule::in(array_keys(Bill::RECURRENCES))],
            'status' => ['required', Rule::in(array_keys(Bill::STATUSES))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
