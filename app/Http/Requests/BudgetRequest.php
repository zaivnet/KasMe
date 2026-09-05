<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BudgetRequest extends FormRequest
{
    public function rules(): array
    {
        $budget = $this->route('budget');

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)->where('type', 'expense')),
                Rule::unique('budgets')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->where('month', $this->integer('month'))
                    ->where('year', $this->integer('year')))
                    ->ignore($budget?->id),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,9999'],
        ];
    }
}
