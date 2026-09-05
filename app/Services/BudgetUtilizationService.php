<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class BudgetUtilizationService
{
    public function attach(Collection $budgets, User $user, int $month, int $year): Collection
    {
        if ($budgets->isEmpty()) {
            return $budgets;
        }

        $usage = $user->transactions()
            ->where('type', 'expense')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->selectRaw('category_id, SUM(amount) AS total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets->each(function ($budget) use ($usage): void {
            $budget->setAttribute('used_amount', (string) ($usage[$budget->category_id] ?? '0.00'));
        });
    }
}
