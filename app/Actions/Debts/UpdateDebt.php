<?php

namespace App\Actions\Debts;

use App\Models\Debt;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDebt
{
    public function handle(Debt $debt, array $data): Debt
    {
        return DB::transaction(function () use ($debt, $data): Debt {
            $locked = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $paid = BigDecimal::of((string) $locked->payments()->sum('amount'));
            $original = BigDecimal::of((string) $data['original_amount']);

            if ($original->isLessThan($paid)) {
                throw ValidationException::withMessages([
                    'original_amount' => 'Jumlah awal tidak boleh lebih kecil dari total pembayaran yang tercatat.',
                ]);
            }
            if ($locked->payments()->exists() && $data['type'] !== $locked->type) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis tidak dapat diubah setelah pembayaran memengaruhi akun.',
                ]);
            }

            $remaining = $original->minus($paid)->toScale(2);
            $locked->update(array_merge($data, [
                'remaining_amount' => (string) $remaining,
                'status' => $remaining->isZero() ? 'paid' : 'active',
            ]));

            return $locked;
        });
    }
}
