<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class SavingGoalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'target_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'target_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
