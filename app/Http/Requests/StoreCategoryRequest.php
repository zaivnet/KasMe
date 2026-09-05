<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Category::TYPES))],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('parent_id') || $validator->errors()->has('parent_id')) {
                return;
            }

            $parent = Category::ownedBy($this->user())->find($this->integer('parent_id'));
            if ($parent && $parent->type !== $this->input('type')) {
                $validator->errors()->add('parent_id', 'Kategori induk harus memiliki jenis yang sama.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
