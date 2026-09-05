<?php

namespace App\Actions\DebtPayments;

use App\Models\Debt;
use App\Models\DebtPayment;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DeleteDebtPayment
{
    public function handle(DebtPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = DebtPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $debt = Debt::whereKey($lockedPayment->debt_id)->lockForUpdate()->firstOrFail();
            $remaining = BigDecimal::of((string) $debt->remaining_amount)
                ->plus((string) $lockedPayment->amount)->toScale(2);

            $lockedPayment->delete();
            $debt->update(['remaining_amount' => (string) $remaining, 'status' => 'active']);
        });
    }
}
