<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Services\BudgetUtilizationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request, BudgetUtilizationService $utilization): View
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $filters = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,9999'],
        ]);
        $month = (int) ($filters['month'] ?? $now->month);
        $year = (int) ($filters['year'] ?? $now->year);

        $budgets = $request->user()->budgets()->with('category')
            ->where('month', $month)->where('year', $year)
            ->orderBy('category_id')->paginate(20)->withQueryString();
        $budgets->setCollection($utilization->attach($budgets->getCollection(), $request->user(), $month, $year));

        return view('budgets.index', compact('budgets', 'month', 'year'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Budget::class);
        $now = CarbonImmutable::now(config('app.timezone'));

        return view('budgets.create', [
            'categories' => $this->expenseCategories($request),
            'defaultMonth' => $request->integer('month', $now->month),
            'defaultYear' => $request->integer('year', $now->year),
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = $request->user()->budgets()->create($request->validated());

        return redirect()->route('budgets.index', ['month' => $budget->month, 'year' => $budget->year])
            ->with('success', 'Anggaran berhasil dibuat.');
    }

    public function edit(Request $request, Budget $budget): View
    {
        Gate::authorize('update', $budget);

        return view('budgets.edit', [
            'budget' => $budget,
            'categories' => $this->expenseCategories($request, $budget->category_id),
        ]);
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $budget->update($request->validated());

        return redirect()->route('budgets.index', ['month' => $budget->month, 'year' => $budget->year])
            ->with('success', 'Anggaran berhasil diperbarui.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        Gate::authorize('delete', $budget);
        $month = $budget->month;
        $year = $budget->year;
        $budget->delete();

        return redirect()->route('budgets.index', compact('month', 'year'))
            ->with('success', 'Anggaran berhasil dihapus.');
    }

    private function expenseCategories(Request $request, ?int $includeCategory = null): Collection
    {
        return $request->user()->categories()->where('type', 'expense')
            ->where(fn ($query) => $query->where('is_active', true)
                ->when($includeCategory, fn ($query, $id) => $query->orWhereKey($id)))
            ->orderBy('name')->get();
    }
}
