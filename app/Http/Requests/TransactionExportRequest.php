<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('user_id', $this->user()->id)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'type' => ['nullable', Rule::in(array_keys(Transaction::TYPES))],
        ];
    }
}
