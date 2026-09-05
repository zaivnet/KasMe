<?php

namespace App\Services;

use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function forUser(User $user, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $query = $this->transactionQuery($user, $filters, $from, $to);

        $totals = (clone $query)->selectRaw(
            "COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income_total, ".
            "COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense_total, ".
            "COALESCE(SUM(CASE WHEN type = 'adjustment' AND adjustment_direction = 'increase' THEN amount ELSE 0 END), 0) AS adjustment_increase, ".
            "COALESCE(SUM(CASE WHEN type = 'adjustment' AND adjustment_direction = 'decrease' THEN amount ELSE 0 END), 0) AS adjustment_decrease"
        )->first();

        $transferFees = $this->transferFees($user, $filters, $from, $to);
        $income = BigDecimal::of((string) $totals->income_total)->toScale(2);
        $expense = BigDecimal::of((string) $totals->expense_total)->toScale(2);
        $fees = BigDecimal::of($transferFees)->toScale(2);

        return [
            'dateFrom' => $from,
            'dateTo' => $to,
            'periodLabel' => $this->periodLabel($user, $from, $to),
            'incomeTotal' => (string) $income,
            'expenseTotal' => (string) $expense,
            'transferFees' => (string) $fees,
            'netCashFlow' => (string) $income->minus($expense)->minus($fees)->toScale(2),
            'adjustmentIncrease' => BigDecimal::of((string) $totals->adjustment_increase)->toScale(2)->__toString(),
            'adjustmentDecrease' => BigDecimal::of((string) $totals->adjustment_decrease)->toScale(2)->__toString(),
            'categoryBreakdown' => $this->categoryBreakdown($query),
            'accountBreakdown' => $this->accountBreakdown($query),
            'dailyChart' => $this->dailyChart($user, $query),
            'transactions' => (clone $query)->with(['account', 'category'])
                ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(20)->withQueryString(),
        ];
    }

    public function exportQuery(User $user, array $filters): Builder
    {
        [$from, $to] = $this->dateRange($filters);

        return $this->transactionQuery($user, $filters, $from, $to);
    }

    private function transactionQuery(User $user, array $filters, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $user->transactions()->getQuery()->whereDate('transactions.transaction_date', '>=', $from->toDateString())
            ->whereDate('transactions.transaction_date', '<=', $to->toDateString())
            ->when($filters['account_id'] ?? null, fn (Builder $query, $id) => $query->where('transactions.account_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $id) => $query->where('transactions.category_id', $id))
            ->when($filters['type'] ?? null, fn (Builder $query, $type) => $query->where('transactions.type', $type));
    }

    private function transferFees(User $user, array $filters, CarbonImmutable $from, CarbonImmutable $to): string
    {
        if (($filters['category_id'] ?? null) || ! in_array($filters['type'] ?? null, [null, 'expense'], true)) {
            return '0.00';
        }

        return BigDecimal::of((string) $user->transfers()
            ->whereDate('transfer_date', '>=', $from->toDateString())->whereDate('transfer_date', '<=', $to->toDateString())
            ->when($filters['account_id'] ?? null, fn (Builder $query, $id) => $query->where('from_account_id', $id))
            ->sum('fee'))->toScale(2)->__toString();
    }

    private function categoryBreakdown(Builder $query): array
    {
        return (clone $query)->whereIn('transactions.type', ['income', 'expense'])
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Tanpa kategori') AS name, transactions.type, SUM(transactions.amount) AS total")
            ->groupBy('categories.id', 'categories.name', 'transactions.type')->orderByDesc('total')->get()->all();
    }

    private function accountBreakdown(Builder $query): array
    {
        return (clone $query)->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->selectRaw('accounts.name, '.
                "SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE 0 END) AS income_total, ".
                "SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount ELSE 0 END) AS expense_total, ".
                "SUM(CASE WHEN transactions.type = 'adjustment' AND transactions.adjustment_direction = 'increase' THEN transactions.amount ELSE 0 END) AS adjustment_increase, ".
                "SUM(CASE WHEN transactions.type = 'adjustment' AND transactions.adjustment_direction = 'decrease' THEN transactions.amount ELSE 0 END) AS adjustment_decrease")
            ->groupBy('accounts.id', 'accounts.name')->orderBy('accounts.name')->get()->all();
    }

    private function dailyChart(User $user, Builder $query): array
    {
        $rows = (clone $query)->whereIn('type', ['income', 'expense'])
            ->selectRaw('transaction_date, type, SUM(amount) AS total')
            ->groupBy('transaction_date', 'type')->orderBy('transaction_date')->get();
        $dates = $rows->pluck('transaction_date')->map->format('Y-m-d')->unique()->values();
        $lookup = $rows->keyBy(fn ($row) => $row->transaction_date->format('Y-m-d').'|'.$row->type);

        return [
            'labels' => $dates->map(fn ($date) => $user->setting->formatDate(CarbonImmutable::parse($date)))->all(),
            'income' => $dates->map(fn ($date) => (float) ($lookup[$date.'|income']->total ?? 0))->all(),
            'expense' => $dates->map(fn ($date) => (float) ($lookup[$date.'|expense']->total ?? 0))->all(),
        ];
    }

    private function dateRange(array $filters): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        return match ($filters['period']) {
            'daily' => [$now->startOfDay(), $now->endOfDay()],
            'weekly' => [$now->startOfWeek(), $now->endOfWeek()],
            'yearly' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [CarbonImmutable::parse($filters['date_from'], config('app.timezone'))->startOfDay(), CarbonImmutable::parse($filters['date_to'], config('app.timezone'))->endOfDay()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    private function periodLabel(User $user, CarbonImmutable $from, CarbonImmutable $to): string
    {
        return $user->setting->formatDate($from).' – '.$user->setting->formatDate($to);
    }
}
