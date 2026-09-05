<?php

namespace App\Actions\SavingGoalTransactions;

use App\Models\SavingGoal;
use App\Models\SavingGoalTransaction;
use App\Services\SavingGoalProgressService;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteSavingGoalTransaction
{
    public function __construct(private SavingGoalProgressService $progress) {}

    public function handle(SavingGoalTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $lockedTransaction = SavingGoalTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $goal = SavingGoal::whereKey($lockedTransaction->saving_goal_id)->lockForUpdate()->firstOrFail();
            $current = $this->progress->calculate($goal);
            $effect = BigDecimal::of((string) $lockedTransaction->amount)
                ->multipliedBy($lockedTransaction->type === 'contribution' ? 1 : -1);
            $newProgress = $current->minus($effect);
            if ($newProgress->isNegative()) {
                throw ValidationException::withMessages(['transaction' => 'Pembatalan ini akan membuat progres tabungan menjadi negatif.']);
            }

            $lockedTransaction->delete();
            $goal->update(['status' => $this->progress->status($goal, $newProgress)]);
        });
    }
}
