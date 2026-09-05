<?php

namespace App\Actions\SavingGoals;

use App\Models\SavingGoal;
use App\Services\SavingGoalProgressService;
use Illuminate\Support\Facades\DB;

class UpdateSavingGoal
{
    public function __construct(private SavingGoalProgressService $progress) {}

    public function handle(SavingGoal $goal, array $data): SavingGoal
    {
        return DB::transaction(function () use ($goal, $data): SavingGoal {
            $locked = SavingGoal::whereKey($goal->id)->lockForUpdate()->firstOrFail();
            $requestedStatus = $data['status'];
            $locked->update($data);
            $progress = $this->progress->calculate($locked);
            $locked->update(['status' => $requestedStatus === 'cancelled'
                ? 'cancelled'
                : ($progress->isGreaterThanOrEqualTo((string) $locked->target_amount) ? 'completed' : 'active')]);

            return $locked;
        });
    }
}
