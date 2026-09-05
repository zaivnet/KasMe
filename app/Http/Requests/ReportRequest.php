<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly', 'custom'])],
            'date_from' => [Rule::requiredIf($this->input('period') === 'custom'), 'nullable', 'date'],
            'date_to' => [Rule::requiredIf($this->input('period') === 'custom'), 'nullable', 'date', 'after_or_equal:date_from'],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'))],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'type' => ['nullable', Rule::in(array_keys(Transaction::TYPES))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['period' => $this->input('period', 'monthly')]);
    }
}
