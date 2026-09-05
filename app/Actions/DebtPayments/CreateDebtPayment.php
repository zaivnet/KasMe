<?php

namespace App\Actions\DebtPayments;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtPayment;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDebtPayment
{
    public function handle(Debt $debt, array $data): DebtPayment
    {
        return DB::transaction(function () use ($debt, $data): DebtPayment {
            $locked = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $account = Account::ownedBy($debt->user)->whereKey($data['account_id'])->lockForUpdate()->first();
            if (! $account) {
                throw ValidationException::withMessages(['account_id' => 'Akun yang dipilih tidak tersedia.']);
            }

            $amount = BigDecimal::of((string) $data['amount']);
            $remaining = BigDecimal::of((string) $locked->remaining_amount);
            if ($amount->isGreaterThan($remaining)) {
                throw ValidationException::withMessages(['amount' => 'Pembayaran tidak boleh melebihi jumlah tersisa.']);
            }

            $payment = $locked->payments()->create($data);
            $newRemaining = $remaining->minus($amount)->toScale(2);
            $locked->update(['remaining_amount' => (string) $newRemaining, 'status' => $newRemaining->isZero() ? 'paid' : 'active']);

            return $payment;
        });
    }
}
