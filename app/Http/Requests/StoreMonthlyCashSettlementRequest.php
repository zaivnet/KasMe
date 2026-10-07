<?php

namespace App\Http\Requests;

use App\Models\MonthlyCashSettlement;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyCashSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MonthlyCashSettlement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'date'],
            'settled_amount' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'settled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Periode setoran wajib dipilih.',
            'settled_amount.required' => 'Jumlah yang disetor wajib diisi.',
            'settled_amount.numeric' => 'Jumlah yang disetor harus berupa angka.',
            'settled_amount.min' => 'Jumlah yang disetor minimal 0.',
            'settled_at.required' => 'Tanggal setoran wajib diisi.',
            'settled_at.date' => 'Format tanggal setoran tidak valid.',
        ];
    }
}
