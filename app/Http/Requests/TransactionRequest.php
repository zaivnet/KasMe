<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class TransactionRequest extends FormRequest
{
    public function rules(): array
    {
        $currentCategoryId = $this->route('transaction')?->category_id;

        return [
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'))],
            'category_id' => [
                Rule::requiredIf(in_array($this->input('type'), ['income', 'expense'], true)),
                Rule::prohibitedIf($this->input('type') === 'adjustment'),
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)
                    ->where(fn ($q) => $q->where('is_active', true)->when($currentCategoryId, fn ($sq, $id) => $sq->orWhere('id', $id)))),
            ],
            'type' => ['required', Rule::in(array_keys(Transaction::TYPES))],
            'adjustment_direction' => [Rule::requiredIf($this->input('type') === 'adjustment'), 'nullable', Rule::in(array_keys(Transaction::ADJUSTMENT_DIRECTIONS))],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'extensions:jpg,jpeg,png,pdf', 'max:5120'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('category_id') || ! $this->filled('category_id')) {
                return;
            }

            $category = Category::ownedBy($this->user())->find($this->integer('category_id'));
            if ($this->input('type') === 'adjustment') {
                $validator->errors()->add('category_id', 'Penyesuaian tidak boleh menggunakan kategori pemasukan atau pengeluaran.');
            } elseif ($category && $category->type !== $this->input('type')) {
                $validator->errors()->add('category_id', 'Jenis kategori harus sesuai dengan jenis transaksi.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== 'adjustment') {
            $this->merge(['adjustment_direction' => null]);
        }
    }
}
