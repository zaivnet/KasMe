<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Account::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'opening_balance' => ['required', 'numeric', 'decimal:0,2', 'between:-9999999999999999.99,9999999999999999.99'],
            'currency' => ['required', 'string', 'max:10', 'regex:/^[A-Z]{3,10}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'IDR')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
