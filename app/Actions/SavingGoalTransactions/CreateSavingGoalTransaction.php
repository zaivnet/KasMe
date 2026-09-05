<?php

namespace App\Actions\SavingGoalTransactions;

use App\Models\Account;
use App\Models\SavingGoal;
use App\Models\SavingGoalTransaction;
use App\Services\SavingGoalProgressService;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSavingGoalTransaction
{
    public function __construct(private SavingGoalProgressService $progress) {}

    public function handle(SavingGoal $goal, array $data): SavingGoalTransaction
    {
        return DB::transaction(function () use ($goal, $data): SavingGoalTransaction {
            $locked = SavingGoal::whereKey($goal->id)->lockForUpdate()->firstOrFail();
            if (! Account::ownedBy($goal->user)->whereKey($data['account_id'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['account_id' => 'Akun yang dipilih tidak tersedia.']);
            }

            $current = $this->progress->calculate($locked);
            $amount = BigDecimal::of((string) $data['amount']);
            if ($data['type'] === 'withdrawal' && $amount->isGreaterThan($current)) {
                throw ValidationException::withMessages(['amount' => 'Penarikan tidak boleh melebihi dana yang tersimpan.']);
            }

            $transaction = $locked->transactions()->create($data);
            $newProgress = $data['type'] === 'contribution' ? $current->plus($amount) : $current->minus($amount);
            $locked->update(['status' => $this->progress->status($locked, $newProgress)]);

            return $transaction;
        });
    }
}
