<?php

namespace App\Http\Controllers;

use App\Actions\Debts\UpdateDebt;
use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\UpdateDebtRequest;
use App\Models\Debt;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DebtController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['type' => ['nullable', 'in:debt,receivable'], 'status' => ['nullable', 'in:active,paid,overdue']]);
        $today = CarbonImmutable::today(config('app.timezone'));
        $debts = $request->user()->debts()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(($filters['status'] ?? null) === 'paid', fn ($query) => $query->where('remaining_amount', 0))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('remaining_amount', '>', 0)
                ->where(fn ($query) => $query->whereNull('due_date')->orWhereDate('due_date', '>=', $today)))
            ->when(($filters['status'] ?? null) === 'overdue', fn ($query) => $query->where('remaining_amount', '>', 0)->whereDate('due_date', '<', $today))
            ->orderByRaw('CASE WHEN remaining_amount = 0 THEN 1 ELSE 0 END')->orderBy('due_date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('debts.index', compact('debts', 'filters'));
    }

    public function create(): View
    {
        Gate::authorize('create', Debt::class);

        return view('debts.create', ['types' => Debt::TYPES]);
    }

    public function store(StoreDebtRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $debt = $request->user()->debts()->create(array_merge($data, ['remaining_amount' => $data['original_amount'], 'status' => 'active']));

        return redirect()->route('debts.show', $debt)->with('success', 'Catatan utang atau piutang berhasil dibuat.');
    }

    public function show(Request $request, Debt $debt): View
    {
        Gate::authorize('view', $debt);

        return view('debts.show', [
            'debt' => $debt->load(['payments' => fn ($query) => $query->with('account')->orderByDesc('payment_date')->orderByDesc('id')]),
            'accounts' => $request->user()->accounts()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Debt $debt): View
    {
        Gate::authorize('update', $debt);

        return view('debts.edit', ['debt' => $debt, 'types' => Debt::TYPES]);
    }

    public function update(UpdateDebtRequest $request, Debt $debt, UpdateDebt $action): RedirectResponse
    {
        $action->handle($debt, $request->validated());

        return redirect()->route('debts.show', $debt)->with('success', 'Catatan utang atau piutang berhasil diperbarui.');
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        Gate::authorize('delete', $debt);
        $debt->delete();

        return redirect()->route('debts.index')->with('success', 'Catatan diarsipkan. Dampak pembayaran pada akun tetap tersimpan.');
    }
}
