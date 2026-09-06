<?php

namespace App\Http\Controllers;

use App\Actions\SavingGoals\UpdateSavingGoal;
use App\Http\Requests\StoreSavingGoalRequest;
use App\Http\Requests\UpdateSavingGoalRequest;
use App\Models\SavingGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SavingGoalController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:active,completed,cancelled']]);
        $goals = $request->user()->savingGoals()->withProgress()
            ->when(($filters['status'] ?? null) === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('status', 'active'))
            ->when(($filters['status'] ?? null) === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END")
            ->orderBy('target_date')->paginate(20)->withQueryString();

        return view('saving-goals.index', compact('goals', 'filters'));
    }

    public function create(): View
    {
        Gate::authorize('create', SavingGoal::class);

        return view('saving-goals.create');
    }

    public function store(StoreSavingGoalRequest $request): RedirectResponse
    {
        $goal = $request->user()->savingGoals()->create(array_merge($request->validated(), ['status' => 'active']));

        return redirect()->route('saving-goals.show', $goal)->with('success', 'Target tabungan berhasil dibuat.');
    }

    public function show(Request $request, SavingGoal $savingGoal): View
    {
        Gate::authorize('view', $savingGoal);
        $savingGoal->loadProgress()
            ->load(['transactions' => fn ($query) => $query->with('account')->orderByDesc('transaction_date')->orderByDesc('id')]);

        return view('saving-goals.show', [
            'goal' => $savingGoal,
            'accounts' => $request->user()->accounts()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(SavingGoal $savingGoal): View
    {
        Gate::authorize('update', $savingGoal);

        return view('saving-goals.edit', ['goal' => $savingGoal->loadProgress()]);
    }

    public function update(UpdateSavingGoalRequest $request, SavingGoal $savingGoal, UpdateSavingGoal $action): RedirectResponse
    {
        $action->handle($savingGoal, $request->validated());

        return redirect()->route('saving-goals.show', $savingGoal)->with('success', 'Target tabungan berhasil diperbarui.');
    }

    public function destroy(SavingGoal $savingGoal): RedirectResponse
    {
        Gate::authorize('delete', $savingGoal);
        $savingGoal->delete();

        return redirect()->route('saving-goals.index')->with('success', 'Target tabungan diarsipkan. Dampak pada akun tetap tersimpan.');
    }
}
