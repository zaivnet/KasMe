<?php

namespace App\Actions\DebtPayments;

use App\Models\Account;
use App\Models\Debt;
use App\Models\DebtPayment;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateDebtPayment
{
    public function handle(DebtPayment $payment, array $data): DebtPayment
    {
        return DB::transaction(function () use ($payment, $data): DebtPayment {
            $lockedPayment = DebtPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $debt = Debt::whereKey($lockedPayment->debt_id)->lockForUpdate()->firstOrFail();
            $account = Account::ownedBy($debt->user)->whereKey($data['account_id'])->lockForUpdate()->first();
            if (! $account) {
                throw ValidationException::withMessages(['account_id' => 'Akun yang dipilih tidak tersedia.']);
            }

            $available = BigDecimal::of((string) $debt->remaining_amount)->plus((string) $lockedPayment->amount);
            $amount = BigDecimal::of((string) $data['amount']);
            if ($amount->isGreaterThan($available)) {
                throw ValidationException::withMessages(['amount' => 'Pembayaran tidak boleh melebihi jumlah tersisa.']);
            }

            $lockedPayment->update($data);
            $remaining = $available->minus($amount)->toScale(2);
            $debt->update(['remaining_amount' => (string) $remaining, 'status' => $remaining->isZero() ? 'paid' : 'active']);

            return $lockedPayment;
        });
    }
}
