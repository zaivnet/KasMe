<?php

namespace App\Services;

use App\Models\MonthlyCashSettlement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyCashService
{
    /**
     * Get timezone-aware current period boundaries.
     */
    public function currentPeriod(User $user, ?CarbonImmutable $reference = null): array
    {
        $timezone = $user->setting?->timezone ?: config('app.timezone', 'Asia/Jakarta');
        $now = $reference ?: CarbonImmutable::now($timezone);

        return [
            'period' => $now->startOfMonth(),
            'start' => $now->startOfMonth(),
            'end' => $now->endOfMonth(),
            'label' => $now->locale('id')->translatedFormat('F Y'),
        ];
    }

    /**
     * Get timezone-aware previous period boundaries.
     */
    public function previousPeriod(User $user, ?CarbonImmutable $reference = null): array
    {
        $timezone = $user->setting?->timezone ?: config('app.timezone', 'Asia/Jakarta');
        $now = $reference ?: CarbonImmutable::now($timezone);
        $prev = $now->subMonthNoOverflow();

        return [
            'period' => $prev->startOfMonth(),
            'start' => $prev->startOfMonth(),
            'end' => $prev->endOfMonth(),
            'label' => $prev->locale('id')->translatedFormat('F Y'),
        ];
    }

    /**
     * Calculate period cash position for a user in [start, end].
     *
     * Formula:
     *   + Income
     *   - Expense
     *   + Adjustment Increase
     *   - Adjustment Decrease
     *   - Transfer Fees (internal transfer principal cancels out across user's accounts)
     *   - Debt Repayments (debt.type == 'debt')
     *   + Receivable Collections (debt.type == 'receivable')
     *   - Saving Goal Contributions
     *   + Saving Goal Withdrawals
     */
    public function calculatePeriodCash(User $user, CarbonImmutable|string $start, CarbonImmutable|string|null $end = null): string
    {
        $details = $this->calculatePeriodDetails($user, $start, $end);

        return $details['net_cash'];
    }

    /**
     * Detailed period cash breakdown.
     */
    public function calculatePeriodDetails(User $user, CarbonImmutable|string $start, CarbonImmutable|string|null $end = null): array
    {
        if (is_string($start)) {
            $tz = $user->setting?->timezone ?: config('app.timezone', 'Asia/Jakarta');
            $parsed = CarbonImmutable::parse($start, $tz);
            $start = $parsed->startOfMonth();
            $end = $end ? (is_string($end) ? CarbonImmutable::parse($end, $tz)->endOfDay() : $end) : $parsed->endOfMonth();
        } elseif ($end === null) {
            $end = $start->endOfMonth();
        } elseif (is_string($end)) {
            $tz = $user->setting?->timezone ?: config('app.timezone', 'Asia/Jakarta');
            $end = CarbonImmutable::parse($end, $tz)->endOfDay();
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        // 1. Transactions
        $txTotals = $user->transactions()
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income, ".
                "COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense, ".
                "COALESCE(SUM(CASE WHEN type = 'adjustment' AND adjustment_direction = 'increase' THEN amount ELSE 0 END), 0) AS adj_increase, ".
                "COALESCE(SUM(CASE WHEN type = 'adjustment' AND adjustment_direction = 'decrease' THEN amount ELSE 0 END), 0) AS adj_decrease"
            )->first();

        $income = BigDecimal::of((string) ($txTotals->income ?? 0))->toScale(2);
        $expense = BigDecimal::of((string) ($txTotals->expense ?? 0))->toScale(2);
        $adjIncrease = BigDecimal::of((string) ($txTotals->adj_increase ?? 0))->toScale(2);
        $adjDecrease = BigDecimal::of((string) ($txTotals->adj_decrease ?? 0))->toScale(2);

        // 2. Transfer fees (principal cancels out across user's accounts)
        $fees = BigDecimal::of((string) $user->transfers()
            ->whereDate('transfer_date', '>=', $startDate)
            ->whereDate('transfer_date', '<=', $endDate)
            ->sum('fee'))->toScale(2);

        // 3. Debt payments
        $debtTotals = $user->debts()
            ->join('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->whereDate('debt_payments.payment_date', '>=', $startDate)
            ->whereDate('debt_payments.payment_date', '<=', $endDate)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN debts.type = 'debt' THEN debt_payments.amount ELSE 0 END), 0) AS debt_outflow, ".
                "COALESCE(SUM(CASE WHEN debts.type = 'receivable' THEN debt_payments.amount ELSE 0 END), 0) AS receivable_inflow"
            )->first();

        $debtOutflow = BigDecimal::of((string) ($debtTotals->debt_outflow ?? 0))->toScale(2);
        $receivableInflow = BigDecimal::of((string) ($debtTotals->receivable_inflow ?? 0))->toScale(2);

        // 4. Saving goal movements
        $savingTotals = $user->savingGoals()
            ->join('saving_goal_transactions', 'saving_goals.id', '=', 'saving_goal_transactions.saving_goal_id')
            ->whereDate('saving_goal_transactions.transaction_date', '>=', $startDate)
            ->whereDate('saving_goal_transactions.transaction_date', '<=', $endDate)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN saving_goal_transactions.type = 'contribution' THEN saving_goal_transactions.amount ELSE 0 END), 0) AS contribution, ".
                "COALESCE(SUM(CASE WHEN saving_goal_transactions.type = 'withdrawal' THEN saving_goal_transactions.amount ELSE 0 END), 0) AS withdrawal"
            )->first();

        $savingContribution = BigDecimal::of((string) ($savingTotals->contribution ?? 0))->toScale(2);
        $savingWithdrawal = BigDecimal::of((string) ($savingTotals->withdrawal ?? 0))->toScale(2);

        // Net period cash
        $netCash = $income
            ->minus($expense)
            ->plus($adjIncrease)
            ->minus($adjDecrease)
            ->minus($fees)
            ->minus($debtOutflow)
            ->plus($receivableInflow)
            ->minus($savingContribution)
            ->plus($savingWithdrawal)
            ->toScale(2);

        return [
            'income' => (string) $income,
            'expense' => (string) $expense,
            'adjustment_increase' => (string) $adjIncrease,
            'adjustments_increase' => (string) $adjIncrease,
            'adjustment_decrease' => (string) $adjDecrease,
            'adjustments_decrease' => (string) $adjDecrease,
            'transfer_fees' => (string) $fees,
            'debt_outflow' => (string) $debtOutflow,
            'debt_payments' => (string) $debtOutflow,
            'receivable_inflow' => (string) $receivableInflow,
            'receivable_payments' => (string) $receivableInflow,
            'saving_contribution' => (string) $savingContribution,
            'saving_contributions' => (string) $savingContribution,
            'saving_withdrawal' => (string) $savingWithdrawal,
            'saving_withdrawals' => (string) $savingWithdrawal,
            'net_cash' => (string) $netCash,
            'period_cash' => (string) $netCash,
        ];
    }

    /**
     * Check if user has any recorded activity in [start, end].
     */
    public function hasActivityInPeriod(User $user, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        if ($user->transactions()->whereDate('transaction_date', '>=', $startDate)->whereDate('transaction_date', '<=', $endDate)->exists()) {
            return true;
        }

        if ($user->transfers()->whereDate('transfer_date', '>=', $startDate)->whereDate('transfer_date', '<=', $endDate)->exists()) {
            return true;
        }

        if ($user->debts()->join('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->whereDate('debt_payments.payment_date', '>=', $startDate)->whereDate('debt_payments.payment_date', '<=', $endDate)->exists()) {
            return true;
        }

        if ($user->savingGoals()->join('saving_goal_transactions', 'saving_goals.id', '=', 'saving_goal_transactions.saving_goal_id')
            ->whereDate('saving_goal_transactions.transaction_date', '>=', $startDate)->whereDate('saving_goal_transactions.transaction_date', '<=', $endDate)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Calculate outstanding cash liability to the boss up to current moment.
     *
     * Concept:
     *   Outstanding Cash = Current Period Cash + sum(historical closed period outstanding amounts)
     *
     * For each closed historical period:
     *   - If settled: outstanding = period_balance_snapshot - settled_amount
     *   - If unsettled: outstanding = calculated_period_cash
     *
     * Performance:
     *   Uses 5 database-level grouped queries across all closed historical records:
     *   1. transactions (income, expense, adj_increase, adj_decrease) grouped by month
     *   2. transfers (fees) grouped by month
     *   3. debts & debt_payments (outflows & inflows) grouped by month
     *   4. saving_goals & saving_goal_transactions (contributions & withdrawals) grouped by month
     *   5. monthly_cash_settlements (existing settlement records)
     *   This eliminates N+1 queries, running in constant O(1) query count.
     */
    public function calculateOutstandingCash(User $user, ?CarbonImmutable $reference = null): array
    {
        $current = $this->currentPeriod($user, $reference);
        $currentMonthStart = $current['start']->toDateString();

        // 1. Current period cash
        $currentCash = BigDecimal::of($this->calculatePeriodCash($user, $current['start'], $current['end']))->toScale(2);

        // Check accountability baseline
        $baseline = $user->setting?->cash_accountability_start_period;
        $isBaselineSet = $baseline !== null;
        $baselineDate = $isBaselineSet ? CarbonImmutable::parse($baseline)->startOfMonth() : null;

        // If baseline is NOT set (existing user onboarding state):
        // Historical periods are NOT treated as outstanding liability to the boss.
        if (! $isBaselineSet) {
            return [
                'outstandingCash' => (string) $currentCash,
                'currentPeriodCash' => (string) $currentCash,
                'historicalCarryOver' => '0.00',
                'hasCarryOver' => false,
                'isBaselineSet' => false,
                'baselineDate' => null,
                'breakdown' => [],
            ];
        }

        $baselineDateString = $baselineDate->toDateString();

        // 2. Fetch all existing active settlements for closed periods
        $settlements = $user->monthlyCashSettlements()
            ->active()
            ->whereDate('period', '<', $currentMonthStart)
            ->whereDate('period', '>=', $baselineDateString)
            ->get()
            ->keyBy(fn ($s) => CarbonImmutable::parse($s->period)->startOfMonth()->toDateString());

        // 3. Database driver date expression
        $driver = DB::connection()->getDriverName();
        $dateExpr = fn (string $col) => $driver === 'sqlite'
            ? "strftime('%Y-%m-01', {$col})"
            : "DATE_FORMAT({$col}, '%Y-%m-01')";

        // 4. Grouped closed-period mutations (only for periods >= baselineDate)
        // Transactions
        $txRows = $user->transactions()
            ->whereDate('transaction_date', '<', $currentMonthStart)
            ->whereDate('transaction_date', '>=', $baselineDateString)
            ->selectRaw(
                "{$dateExpr('transaction_date')} AS period, ".
                "COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income, ".
                "COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense, ".
                "COALESCE(SUM(CASE WHEN type = 'adjustment' AND adjustment_direction = 'increase' THEN amount ELSE 0 END), 0) AS adj_increase, ".
                "COALESCE(SUM(CASE WHEN type = 'adjustment' AND adjustment_direction = 'decrease' THEN amount ELSE 0 END), 0) AS adj_decrease"
            )
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        // Transfer fees
        $transferRows = $user->transfers()
            ->whereDate('transfer_date', '<', $currentMonthStart)
            ->whereDate('transfer_date', '>=', $baselineDateString)
            ->selectRaw("{$dateExpr('transfer_date')} AS period, COALESCE(SUM(fee), 0) AS fees")
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        // Debt payments
        $debtRows = $user->debts()
            ->join('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->whereDate('debt_payments.payment_date', '<', $currentMonthStart)
            ->whereDate('debt_payments.payment_date', '>=', $baselineDateString)
            ->selectRaw(
                "{$dateExpr('debt_payments.payment_date')} AS period, ".
                "COALESCE(SUM(CASE WHEN debts.type = 'debt' THEN debt_payments.amount ELSE 0 END), 0) AS debt_outflow, ".
                "COALESCE(SUM(CASE WHEN debts.type = 'receivable' THEN debt_payments.amount ELSE 0 END), 0) AS receivable_inflow"
            )
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        // Saving goal movements
        $savingRows = $user->savingGoals()
            ->join('saving_goal_transactions', 'saving_goals.id', '=', 'saving_goal_transactions.saving_goal_id')
            ->whereDate('saving_goal_transactions.transaction_date', '<', $currentMonthStart)
            ->whereDate('saving_goal_transactions.transaction_date', '>=', $baselineDateString)
            ->selectRaw(
                "{$dateExpr('saving_goal_transactions.transaction_date')} AS period, ".
                "COALESCE(SUM(CASE WHEN saving_goal_transactions.type = 'contribution' THEN saving_goal_transactions.amount ELSE 0 END), 0) AS contribution, ".
                "COALESCE(SUM(CASE WHEN saving_goal_transactions.type = 'withdrawal' THEN saving_goal_transactions.amount ELSE 0 END), 0) AS withdrawal"
            )
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        // 5. Collect all distinct closed period keys >= baselineDate
        $allPeriods = collect()
            ->merge($settlements->keys())
            ->merge($txRows->keys())
            ->merge($transferRows->keys())
            ->merge($debtRows->keys())
            ->merge($savingRows->keys())
            ->filter(fn ($p) => CarbonImmutable::parse($p)->gte($baselineDate))
            ->unique()
            ->sort()
            ->values();

        $historicalOutstanding = BigDecimal::zero();
        $breakdown = [];

        foreach ($allPeriods as $periodKey) {
            if ($settlements->has($periodKey)) {
                /** @var MonthlyCashSettlement $settlement */
                $settlement = $settlements->get($periodKey);
                $periodOutstanding = BigDecimal::of((string) $settlement->period_balance_snapshot)
                    ->minus((string) $settlement->settled_amount)
                    ->toScale(2);

                $breakdown[$periodKey] = [
                    'period' => $periodKey,
                    'status' => 'settled',
                    'snapshot' => (string) $settlement->period_balance_snapshot,
                    'settled' => (string) $settlement->settled_amount,
                    'outstanding' => (string) $periodOutstanding,
                ];
            } else {
                $tx = $txRows->get($periodKey);
                $tr = $transferRows->get($periodKey);
                $db = $debtRows->get($periodKey);
                $sg = $savingRows->get($periodKey);

                $income = BigDecimal::of((string) ($tx->income ?? 0));
                $expense = BigDecimal::of((string) ($tx->expense ?? 0));
                $adjInc = BigDecimal::of((string) ($tx->adj_increase ?? 0));
                $adjDec = BigDecimal::of((string) ($tx->adj_decrease ?? 0));
                $fee = BigDecimal::of((string) ($tr->fees ?? 0));
                $debtOut = BigDecimal::of((string) ($db->debt_outflow ?? 0));
                $recIn = BigDecimal::of((string) ($db->receivable_inflow ?? 0));
                $sgContr = BigDecimal::of((string) ($sg->contribution ?? 0));
                $sgWith = BigDecimal::of((string) ($sg->withdrawal ?? 0));

                $periodCash = $income
                    ->minus($expense)
                    ->plus($adjInc)
                    ->minus($adjDec)
                    ->minus($fee)
                    ->minus($debtOut)
                    ->plus($recIn)
                    ->minus($sgContr)
                    ->plus($sgWith)
                    ->toScale(2);

                $periodOutstanding = $periodCash;

                $breakdown[$periodKey] = [
                    'period' => $periodKey,
                    'status' => 'unsettled',
                    'snapshot' => null,
                    'settled' => '0.00',
                    'outstanding' => (string) $periodOutstanding,
                ];
            }

            $historicalOutstanding = $historicalOutstanding->plus($periodOutstanding);
        }

        $historicalOutstanding = $historicalOutstanding->toScale(2);
        $totalOutstanding = $currentCash->plus($historicalOutstanding)->toScale(2);

        return [
            'outstandingCash' => (string) $totalOutstanding,
            'currentPeriodCash' => (string) $currentCash,
            'historicalCarryOver' => (string) $historicalOutstanding,
            'hasCarryOver' => ! $historicalOutstanding->isZero(),
            'isBaselineSet' => true,
            'baselineDate' => $baselineDateString,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Dashboard monthly cash accountability summary.
     */
    public function summaryForDashboard(User $user, ?CarbonImmutable $reference = null): array
    {
        $current = $this->currentPeriod($user, $reference);
        $previous = $this->previousPeriod($user, $reference);

        $currentDetails = $this->calculatePeriodDetails($user, $current['start'], $current['end']);
        $previousDetails = $this->calculatePeriodDetails($user, $previous['start'], $previous['end']);

        // Check baseline
        $baseline = $user->setting?->cash_accountability_start_period;
        $isBaselineSet = $baseline !== null;
        $baselineDate = $isBaselineSet ? CarbonImmutable::parse($baseline)->startOfMonth() : null;

        $isPreviousBeforeBaseline = (! $isBaselineSet) || ($baselineDate && $previous['period']->lt($baselineDate));

        // Active settlement for previous period
        $previousSettlement = $user->monthlyCashSettlements()
            ->active()
            ->whereDate('period', $previous['period']->toDateString())
            ->first();

        $hasPreviousData = $this->hasActivityInPeriod($user, $previous['start'], $previous['end'])
            || ($previousSettlement !== null)
            || ! BigDecimal::of($previousDetails['net_cash'])->isZero();

        $outstanding = $this->calculateOutstandingCash($user, $reference);

        return [
            'outstandingCash' => $outstanding['outstandingCash'],
            'historicalCarryOver' => $outstanding['historicalCarryOver'],
            'hasCarryOver' => $outstanding['hasCarryOver'],
            'isBaselineSet' => $outstanding['isBaselineSet'],
            'baselineDate' => $outstanding['baselineDate'],
            'isPreviousBeforeBaseline' => $isPreviousBeforeBaseline,
            'outstandingBreakdown' => $outstanding['breakdown'],

            'currentPeriod' => $current['period']->toDateString(),
            'currentPeriodLabel' => $current['label'],
            'currentPeriodStart' => $current['start']->toDateString(),
            'currentPeriodEnd' => $current['end']->toDateString(),
            'currentPeriodCash' => $currentDetails['net_cash'],
            'currentPeriodIncome' => $currentDetails['income'],
            'currentPeriodExpense' => $currentDetails['expense'],
            'currentPeriodFees' => $currentDetails['transfer_fees'],

            'previousPeriod' => $previous['period']->toDateString(),
            'previousPeriodLabel' => $previous['label'],
            'previousPeriodStart' => $previous['start']->toDateString(),
            'previousPeriodEnd' => $previous['end']->toDateString(),
            'previousPeriodCash' => $previousDetails['net_cash'],
            'previousPeriodSettlement' => $previousSettlement,
            'previousSettlement' => $previousSettlement,
            'previousPeriodSettled' => $previousSettlement !== null,
            'isPreviousPeriodSettled' => $previousSettlement !== null,
            'hasPreviousPeriodData' => $hasPreviousData,
        ];
    }

    /**
     * Record a settlement for a closed period.
     *
     * Audit Integrity (Sprint 19.5.2):
     *   - Voided settlement records are IMMUTABLE — never updated or re-activated.
     *   - Re-settlement after void always INSERTs a NEW row, preserving the full
     *     audit trail (original settled_amount, voided_at, void_reason).
     *
     * Concurrency Strategy (Sprint 19.5.3):
     *   - The User row is locked first as the serialization point.
     *   - Rationale: SELECT ... FOR UPDATE on zero existing settlement rows provides
     *     NO mutex on MySQL/MariaDB — two concurrent requests could both observe
     *     zero active rows and both INSERT, violating the single-active invariant.
     *   - The User row ALWAYS exists, giving a deterministic lock target.
     *     Two concurrent settle() calls for the same user serialize here; the second
     *     blocks until the first commits, then sees the active row and throws.
     *   - Portability: works on MySQL 8+, MariaDB 10.5+.
     *     On SQLite (test/dev) write transactions are serialized by WAL mode.
     */
    public function settle(User $user, array $validated): MonthlyCashSettlement
    {
        $timezone = $user->setting?->timezone ?: config('app.timezone', 'Asia/Jakarta');
        $now = CarbonImmutable::now($timezone);
        $currentMonthStart = $now->startOfMonth();

        $periodDate = CarbonImmutable::parse($validated['period'], $timezone)->startOfMonth();

        // Enforce: only closed periods can be settled
        if (! $periodDate->lt($currentMonthStart)) {
            throw ValidationException::withMessages([
                'period' => 'Hanya periode bulan yang sudah selesai yang dapat disetorkan.',
            ]);
        }

        // Recalculate period closing balance server-side.
        // Kept outside the transaction to minimize lock-holding time.
        $snapshot = $this->calculatePeriodCash($user, $periodDate->startOfMonth(), $periodDate->endOfMonth());

        $settledAmount = BigDecimal::of((string) $validated['settled_amount'])->toScale(2)->__toString();

        return DB::transaction(function () use ($user, $validated, $periodDate, $snapshot, $settledAmount): MonthlyCashSettlement {
            // --- Serialization point: lock the User row ---
            // This row always exists, making it a portable deterministic mutex.
            // Two concurrent requests for the same user serialize here.
            // We do NOT lock settlement rows: locking zero rows on MySQL/MariaDB
            // provides no exclusion guarantee.
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // --- Active-settlement guard (inside lock scope) ---
            // No other concurrent request can be past the user-row lock for this
            // user until we commit, so this check is now race-free.
            $activeExists = $user->monthlyCashSettlements()
                ->whereDate('period', $periodDate->toDateString())
                ->whereNull('voided_at')
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'period' => 'Setoran untuk periode ini sudah pernah dicatat.',
                ]);
            }

            // Always INSERT a new record.
            // Any voided rows for the same period are never touched — immutable evidence.
            return $user->monthlyCashSettlements()->create([
                'period'                  => $periodDate->toDateString(),
                'period_balance_snapshot' => $snapshot,
                'settled_amount'          => $settledAmount,
                'settled_at'              => $validated['settled_at'],
                'notes'                   => $validated['notes'] ?? null,
                'voided_at'               => null,
                'void_reason'             => null,
            ]);
        });
    }

    /**
     * Void an active settlement (audit-safe cancellation).
     *
     * Concurrency Strategy (Sprint 19.5.3):
     *   Uses the same user-row lock as settle() so that simultaneous
     *   settle/void operations on the same user cannot race.
     *
     * Invariant:
     *   The voided row becomes immutable after this call. Its settled_amount,
     *   settled_at, and period_balance_snapshot are never modified.
     */
    public function voidSettlement(User $user, MonthlyCashSettlement $settlement, string $voidReason): void
    {
        DB::transaction(function () use ($user, $settlement, $voidReason): void {
            // Serialize with the same mutex used by settle().
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Re-fetch with lock to guard against concurrent void on same row.
            $fresh = MonthlyCashSettlement::query()
                ->where('user_id', $user->id)
                ->whereKey($settlement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->isVoided()) {
                // Already voided by another concurrent request — idempotent, no-op.
                return;
            }

            $fresh->update([
                'voided_at'   => now(),
                'void_reason' => $voidReason ?: 'Dibatalkan oleh pengguna',
            ]);
        });
    }

    /**
     * Detect if transactions in the settled period were modified after settlement.
     */
    public function detectDiscrepancy(MonthlyCashSettlement $settlement): ?array
    {
        $periodDate = CarbonImmutable::parse($settlement->period);
        $currentValue = $this->calculatePeriodCash($settlement->user, $periodDate->startOfMonth(), $periodDate->endOfMonth());

        $snapshot = (string) $settlement->period_balance_snapshot;

        if (BigDecimal::of($currentValue)->compareTo(BigDecimal::of($snapshot)) !== 0) {
            return [
                'is_discrepant' => true,
                'snapshot' => $snapshot,
                'snapshot_balance' => $snapshot,
                'recalculated' => $currentValue,
                'current_recalculated_balance' => $currentValue,
                'difference' => (string) BigDecimal::of($currentValue)->minus(BigDecimal::of($snapshot))->toScale(2),
            ];
        }

        return null;
    }
}
