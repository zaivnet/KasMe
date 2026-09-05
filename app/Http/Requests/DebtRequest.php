<?php

namespace App\Http\Requests;

use App\Models\Debt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class DebtRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Debt::TYPES))],
            'person_name' => ['required', 'string', 'max:150'],
            'original_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
