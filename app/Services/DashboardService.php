<?php

namespace App\Services;

use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;

class DashboardService
{
    public function __construct(
        private AccountBalanceService $balanceService,
        private BudgetUtilizationService $budgetUtilization,
    ) {}

    public function forUser(User $user): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $start = $now->startOfMonth()->toDateString();
        $end = $now->endOfMonth()->toDateString();
        $accounts = $user->accounts()->orderBy('name')->get();
        $balances = $this->balanceService->calculateMany($accounts);
        $totalBalance = collect($balances)->reduce(
            fn (BigDecimal $total, string $balance) => $total->plus($balance),
            BigDecimal::zero(),
        )->toScale(2);

        $monthlyTotals = $user->transactions()->whereDate('transaction_date', '>=', $start)->whereDate('transaction_date', '<=', $end)
            ->whereIn('type', ['income', 'expense'])->selectRaw('type, SUM(amount) AS total')
            ->groupBy('type')->pluck('total', 'type');
        $income = BigDecimal::of((string) ($monthlyTotals['income'] ?? 0))->toScale(2);
        $expense = BigDecimal::of((string) ($monthlyTotals['expense'] ?? 0))->toScale(2);
        $fees = BigDecimal::of((string) $user->transfers()->whereDate('transfer_date', '>=', $start)->whereDate('transfer_date', '<=', $end)->sum('fee'))->toScale(2);

        $daily = $user->transactions()->whereDate('transaction_date', '>=', $start)->whereDate('transaction_date', '<=', $end)
            ->whereIn('type', ['income', 'expense'])
            ->selectRaw('transaction_date, type, SUM(amount) AS total')
            ->groupBy('transaction_date', 'type')->orderBy('transaction_date')->get();
        $dates = $daily->pluck('transaction_date')->map->format('Y-m-d')->unique()->values();
        $dailyLookup = $daily->keyBy(fn ($row) => $row->transaction_date->format('Y-m-d').'|'.$row->type);

        $expensesByCategory = $user->transactions()->whereDate('transactions.transaction_date', '>=', $start)->whereDate('transactions.transaction_date', '<=', $end)
            ->where('transactions.type', 'expense')->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Tanpa kategori') AS category_name, SUM(transactions.amount) AS total")
            ->groupBy('categories.id', 'categories.name')->orderByDesc('total')->get();

        $budgets = $user->budgets()->with('category')->where('month', $now->month)
            ->where('year', $now->year)->orderBy('category_id')->get();
        $this->budgetUtilization->attach($budgets, $user, $now->month, $now->year);
        $budgetAmount = $budgets->reduce(
            fn (BigDecimal $total, $budget) => $total->plus((string) $budget->amount), BigDecimal::zero(),
        )->toScale(2);
        $budgetUsed = $budgets->reduce(
            fn (BigDecimal $total, $budget) => $total->plus($budget->usedAmount()), BigDecimal::zero(),
        )->toScale(2);
        $upcomingBills = $user->bills()->with('category')->where('status', '!=', 'paid')
            ->whereDate('due_date', '<=', $now->addDays(30))->orderBy('due_date')->limit(6)->get();

        return [
            'periodLabel' => $now->locale('id')->translatedFormat('F Y'),
            'currency' => $user->setting->currency,
            'totalBalance' => (string) $totalBalance,
            'income' => (string) $income,
            'expense' => (string) $expense,
            'fees' => (string) $fees,
            'netCashFlow' => (string) $income->minus($expense)->minus($fees)->toScale(2),
            'accounts' => $accounts,
            'balances' => $balances,
            'budgets' => $budgets,
            'budgetAmount' => (string) $budgetAmount,
            'budgetUsed' => (string) $budgetUsed,
            'budgetPercentage' => $budgetAmount->isZero() ? 0.0 : $budgetUsed
                ->dividedBy($budgetAmount, 6, RoundingMode::HalfUp)->multipliedBy(100)->toFloat(),
            'upcomingBills' => $upcomingBills,
            'recentTransactions' => $user->transactions()->with(['account', 'category'])->orderByDesc('transaction_date')->orderByDesc('id')->limit(8)->get(),
            'cashFlowChart' => [
                'labels' => $dates->map(fn ($date) => $user->setting->formatDate(CarbonImmutable::parse($date)))->all(),
                'income' => $dates->map(fn ($date) => (float) ($dailyLookup[$date.'|income']->total ?? 0))->all(),
                'expense' => $dates->map(fn ($date) => (float) ($dailyLookup[$date.'|expense']->total ?? 0))->all(),
            ],
            'categoryChart' => [
                'labels' => $expensesByCategory->pluck('category_name')->all(),
                'values' => $expensesByCategory->map(fn ($row) => (float) $row->total)->all(),
            ],
        ];
    }
}
