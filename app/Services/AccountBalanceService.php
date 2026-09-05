<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DebtPayment;
use App\Models\SavingGoalTransaction;
use App\Models\Transaction;
use App\Models\Transfer;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Collection;

class AccountBalanceService
{
    private const EFFECT_SQL = "COALESCE(SUM(CASE WHEN type = 'income' THEN amount WHEN type = 'expense' THEN -amount WHEN type = 'adjustment' AND adjustment_direction = 'increase' THEN amount WHEN type = 'adjustment' AND adjustment_direction = 'decrease' THEN -amount ELSE 0 END), 0)";

    public function calculate(Account $account): string
    {
        return $this->calculateMany(new Collection([$account]))[$account->id];
    }

    /** @return array<int, string> */
    public function calculateMany(Collection $accounts): array
    {
        $effects = Transaction::query()->whereIn('account_id', $accounts->modelKeys())
            ->selectRaw('account_id, '.self::EFFECT_SQL.' AS effect')
            ->groupBy('account_id')->pluck('effect', 'account_id');

        $balances = $accounts->mapWithKeys(fn (Account $account) => [
            $account->id => (string) BigDecimal::of($account->opening_balance)
                ->plus((string) ($effects[$account->id] ?? 0))->toScale(2),
        ])->all();

        Transfer::query()->whereIn('from_account_id', $accounts->modelKeys())
            ->selectRaw('from_account_id, SUM(amount + fee) AS effect')
            ->groupBy('from_account_id')->pluck('effect', 'from_account_id')
            ->each(function ($effect, $accountId) use (&$balances): void {
                $balances[$accountId] = (string) BigDecimal::of($balances[$accountId])->minus((string) $effect)->toScale(2);
            });

        Transfer::query()->whereIn('to_account_id', $accounts->modelKeys())
            ->selectRaw('to_account_id, SUM(amount) AS effect')
            ->groupBy('to_account_id')->pluck('effect', 'to_account_id')
            ->each(function ($effect, $accountId) use (&$balances): void {
                $balances[$accountId] = (string) BigDecimal::of($balances[$accountId])->plus((string) $effect)->toScale(2);
            });

        DebtPayment::query()->whereIn('account_id', $accounts->modelKeys())
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->selectRaw("account_id, SUM(CASE WHEN debts.type = 'debt' THEN -debt_payments.amount ELSE debt_payments.amount END) AS effect")
            ->groupBy('account_id')->pluck('effect', 'account_id')
            ->each(function ($effect, $accountId) use (&$balances): void {
                if (array_key_exists($accountId, $balances)) {
                    $balances[$accountId] = (string) BigDecimal::of($balances[$accountId])->plus((string) $effect)->toScale(2);
                }
            });

        SavingGoalTransaction::query()->whereIn('account_id', $accounts->modelKeys())
            ->selectRaw("account_id, SUM(CASE WHEN type = 'contribution' THEN -amount ELSE amount END) AS effect")
            ->groupBy('account_id')->pluck('effect', 'account_id')
            ->each(function ($effect, $accountId) use (&$balances): void {
                if (array_key_exists($accountId, $balances)) {
                    $balances[$accountId] = (string) BigDecimal::of($balances[$accountId])->plus((string) $effect)->toScale(2);
                }
            });

        return $balances;
    }
}
