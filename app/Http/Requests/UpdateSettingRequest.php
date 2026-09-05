<?php

namespace App\Http\Requests;

use App\Models\Setting;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'size:3', 'alpha:ascii'],
            'date_format' => ['required', Rule::in(array_keys(Setting::DATE_FORMATS))],
            'timezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'theme' => ['required', Rule::in(array_keys(Setting::THEMES))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
    }
}
