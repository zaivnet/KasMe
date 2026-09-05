<?php

namespace App\Services;

use App\Models\SavingGoal;
use Brick\Math\BigDecimal;

class SavingGoalProgressService
{
    public function calculate(SavingGoal $goal): BigDecimal
    {
        $totals = $goal->transactions()->selectRaw('type, SUM(amount) AS total')->groupBy('type')->pluck('total', 'type');

        return BigDecimal::of((string) ($totals['contribution'] ?? 0))
            ->minus((string) ($totals['withdrawal'] ?? 0))->toScale(2);
    }

    public function status(SavingGoal $goal, BigDecimal $progress): string
    {
        if ($goal->status === 'cancelled') {
            return 'cancelled';
        }

        return $progress->isGreaterThanOrEqualTo((string) $goal->target_amount) ? 'completed' : 'active';
    }
}
