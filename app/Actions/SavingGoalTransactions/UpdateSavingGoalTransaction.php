<?php

namespace App\Actions\SavingGoalTransactions;

use App\Models\Account;
use App\Models\SavingGoal;
use App\Models\SavingGoalTransaction;
use App\Services\SavingGoalProgressService;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSavingGoalTransaction
{
    public function __construct(private SavingGoalProgressService $progress) {}

    public function handle(SavingGoalTransaction $transaction, array $data): SavingGoalTransaction
    {
        return DB::transaction(function () use ($transaction, $data): SavingGoalTransaction {
            $lockedTransaction = SavingGoalTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $goal = SavingGoal::whereKey($lockedTransaction->saving_goal_id)->lockForUpdate()->firstOrFail();
            if (! Account::ownedBy($goal->user)->whereKey($data['account_id'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['account_id' => 'Akun yang dipilih tidak tersedia.']);
            }

            $current = $this->progress->calculate($goal);
            $oldEffect = BigDecimal::of((string) $lockedTransaction->amount)
                ->multipliedBy($lockedTransaction->type === 'contribution' ? 1 : -1);
            $newEffect = BigDecimal::of((string) $data['amount'])->multipliedBy($data['type'] === 'contribution' ? 1 : -1);
            $newProgress = $current->minus($oldEffect)->plus($newEffect);
            if ($newProgress->isNegative()) {
                throw ValidationException::withMessages(['amount' => 'Perubahan ini akan menarik dana melebihi jumlah yang tersimpan.']);
            }

            $lockedTransaction->update($data);
            $goal->update(['status' => $this->progress->status($goal, $newProgress)]);

            return $lockedTransaction;
        });
    }
}
