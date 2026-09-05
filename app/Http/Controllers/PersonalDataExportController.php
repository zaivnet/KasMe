<?php

namespace App\Http\Controllers;

use App\Models\DebtPayment;
use App\Models\SavingGoalTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalDataExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();

        return response()->streamDownload(function () use ($user): void {
            echo '{"exported_at":'.json_encode(now()->toIso8601String(), JSON_THROW_ON_ERROR);
            echo ',"profile":'.json_encode(['name' => $user->name, 'email' => $user->email], JSON_THROW_ON_ERROR);
            echo ',"settings":'.json_encode($user->setting?->only(['currency', 'date_format', 'timezone', 'theme']), JSON_THROW_ON_ERROR);

            $this->writeCollection('accounts', $user->accounts()->withTrashed(), ['id', 'name', 'type', 'opening_balance', 'currency', 'icon', 'color', 'is_active', 'created_at', 'updated_at', 'deleted_at']);
            $this->writeCollection('categories', $user->categories(), ['id', 'parent_id', 'name', 'type', 'icon', 'color', 'is_active', 'created_at', 'updated_at']);
            $this->writeCollection('transactions', $user->transactions()->withTrashed(), ['id', 'account_id', 'category_id', 'type', 'adjustment_direction', 'amount', 'transaction_date', 'description', 'created_at', 'updated_at', 'deleted_at'], true);
            $this->writeCollection('transfers', $user->transfers()->withTrashed(), ['id', 'from_account_id', 'to_account_id', 'amount', 'fee', 'transfer_date', 'description', 'created_at', 'updated_at', 'deleted_at']);
            $this->writeCollection('budgets', $user->budgets(), ['id', 'category_id', 'amount', 'month', 'year', 'created_at', 'updated_at']);
            $this->writeCollection('bills', $user->bills()->withTrashed(), ['id', 'category_id', 'name', 'amount', 'due_date', 'recurrence', 'status', 'notes', 'created_at', 'updated_at', 'deleted_at']);
            $this->writeCollection('debts', $user->debts()->withTrashed(), ['id', 'type', 'person_name', 'original_amount', 'remaining_amount', 'start_date', 'due_date', 'status', 'notes', 'created_at', 'updated_at', 'deleted_at']);
            $this->writeCollection('debt_payments', DebtPayment::query()->whereHas('debt', fn (Builder $query) => $query->withTrashed()->where('user_id', $user->id)), ['id', 'debt_id', 'account_id', 'amount', 'payment_date', 'notes', 'created_at', 'updated_at']);
            $this->writeCollection('saving_goals', $user->savingGoals()->withTrashed(), ['id', 'name', 'target_amount', 'target_date', 'description', 'status', 'created_at', 'updated_at', 'deleted_at']);
            $this->writeCollection('saving_goal_transactions', SavingGoalTransaction::query()->whereHas('savingGoal', fn (Builder $query) => $query->withTrashed()->where('user_id', $user->id)), ['id', 'saving_goal_id', 'account_id', 'type', 'amount', 'transaction_date', 'notes', 'created_at', 'updated_at']);
            echo '}';
        }, 'cadangan-data-pribadi-'.now()->format('Y-m-d-His').'.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    private function writeCollection(string $name, Builder|Relation $query, array $columns, bool $attachmentFlag = false): void
    {
        echo ','.json_encode($name, JSON_THROW_ON_ERROR).':[';
        $first = true;
        foreach ($query->orderBy('id')->lazyById(500) as $model) {
            $row = $model->only($columns);
            if ($attachmentFlag) {
                $row['has_attachment'] = (bool) $model->attachment;
            }
            echo ($first ? '' : ',').json_encode($row, JSON_THROW_ON_ERROR);
            $first = false;
        }
        echo ']';
    }
}
