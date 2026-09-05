<?php

namespace App\Http\Controllers;

use App\Actions\DebtPayments\CreateDebtPayment;
use App\Actions\DebtPayments\DeleteDebtPayment;
use App\Actions\DebtPayments\UpdateDebtPayment;
use App\Http\Requests\StoreDebtPaymentRequest;
use App\Http\Requests\UpdateDebtPaymentRequest;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DebtPaymentController extends Controller
{
    public function store(StoreDebtPaymentRequest $request, Debt $debt, CreateDebtPayment $action): RedirectResponse
    {
        $action->handle($debt, $request->validated());

        return redirect()->route('debts.show', $debt)->with('success', 'Pembayaran dicatat dan saldo akun telah direkonsiliasi.');
    }

    public function edit(Request $request, Debt $debt, DebtPayment $payment): View
    {
        Gate::authorize('update', $payment);

        return view('debts.payments.edit', [
            'debt' => $debt,
            'payment' => $payment,
            'accounts' => $request->user()->accounts()->where(fn ($query) => $query->where('is_active', true)->orWhereKey($payment->account_id))->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateDebtPaymentRequest $request, Debt $debt, DebtPayment $payment, UpdateDebtPayment $action): RedirectResponse
    {
        $action->handle($payment, $request->validated());

        return redirect()->route('debts.show', $debt)->with('success', 'Pembayaran diperbarui dan saldo telah direkonsiliasi.');
    }

    public function destroy(Debt $debt, DebtPayment $payment, DeleteDebtPayment $action): RedirectResponse
    {
        Gate::authorize('delete', $payment);
        $action->handle($payment);

        return redirect()->route('debts.show', $debt)->with('success', 'Pembayaran dibatalkan dan saldo telah direkonsiliasi.');
    }
}
