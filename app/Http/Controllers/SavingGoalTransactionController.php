<?php

namespace App\Http\Controllers;

use App\Actions\SavingGoalTransactions\CreateSavingGoalTransaction;
use App\Actions\SavingGoalTransactions\DeleteSavingGoalTransaction;
use App\Actions\SavingGoalTransactions\UpdateSavingGoalTransaction;
use App\Http\Requests\StoreSavingGoalTransactionRequest;
use App\Http\Requests\UpdateSavingGoalTransactionRequest;
use App\Models\SavingGoal;
use App\Models\SavingGoalTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SavingGoalTransactionController extends Controller
{
    public function store(StoreSavingGoalTransactionRequest $request, SavingGoal $savingGoal, CreateSavingGoalTransaction $action): RedirectResponse
    {
        $action->handle($savingGoal, $request->validated());

        return redirect()->route('saving-goals.show', $savingGoal)->with('success', 'Pergerakan dana dicatat dan saldo akun telah direkonsiliasi.');
    }

    public function edit(Request $request, SavingGoal $savingGoal, SavingGoalTransaction $transaction): View
    {
        Gate::authorize('update', $transaction);

        return view('saving-goals.transactions.edit', [
            'goal' => $savingGoal,
            'transaction' => $transaction,
            'accounts' => $request->user()->accounts()->where(fn ($query) => $query->where('is_active', true)->orWhereKey($transaction->account_id))->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateSavingGoalTransactionRequest $request, SavingGoal $savingGoal, SavingGoalTransaction $transaction, UpdateSavingGoalTransaction $action): RedirectResponse
    {
        $action->handle($transaction, $request->validated());

        return redirect()->route('saving-goals.show', $savingGoal)->with('success', 'Pergerakan dana diperbarui dan saldo telah direkonsiliasi.');
    }

    public function destroy(SavingGoal $savingGoal, SavingGoalTransaction $transaction, DeleteSavingGoalTransaction $action): RedirectResponse
    {
        Gate::authorize('delete', $transaction);
        $action->handle($transaction);

        return redirect()->route('saving-goals.show', $savingGoal)->with('success', 'Pergerakan dana dibatalkan dan saldo telah direkonsiliasi.');
    }
}
