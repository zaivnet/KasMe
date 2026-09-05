<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSavingGoalRequest extends SavingGoalRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('saving_goal')) ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), ['status' => ['required', Rule::in(['active', 'cancelled'])]]);
    }
}
