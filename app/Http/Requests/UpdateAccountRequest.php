<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('account')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'currency' => ['required', 'string', 'max:10', 'regex:/^[A-Z]{3,10}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
            'opening_balance' => ['prohibited'],
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
