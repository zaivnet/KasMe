<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BillController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:unpaid,paid,overdue'],
            'recurrence' => ['nullable', 'in:none,weekly,monthly,yearly'],
            'upcoming' => ['nullable', 'boolean'],
        ]);
        $today = CarbonImmutable::today(config('app.timezone'));

        $bills = $request->user()->bills()->with('category')
            ->when(($filters['status'] ?? null) === 'paid', fn ($query) => $query->where('status', 'paid'))
            ->when(($filters['status'] ?? null) === 'unpaid', fn ($query) => $query->where('status', 'unpaid')->whereDate('due_date', '>=', $today))
            ->when(($filters['status'] ?? null) === 'overdue', fn ($query) => $query->where(fn ($query) => $query
                ->where('status', 'overdue')->orWhere(fn ($query) => $query->where('status', '!=', 'paid')->whereDate('due_date', '<', $today))))
            ->when($filters['recurrence'] ?? null, fn ($query, $recurrence) => $query->where('recurrence', $recurrence))
            ->when(($filters['upcoming'] ?? false), fn ($query) => $query->where('status', '!=', 'paid')
                ->whereBetween('due_date', [$today, $today->addDays(30)]))
            ->orderByRaw("CASE WHEN status = 'paid' THEN 1 ELSE 0 END")
            ->orderBy('due_date')->paginate(20)->withQueryString();

        return view('bills.index', compact('bills', 'filters', 'today'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Bill::class);

        return view('bills.create', [
            'categories' => $this->categories($request),
            'recurrences' => Bill::RECURRENCES,
            'statuses' => Bill::STATUSES,
        ]);
    }

    public function store(StoreBillRequest $request): RedirectResponse
    {
        $request->user()->bills()->create($request->validated());

        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil dibuat.');
    }

    public function edit(Request $request, Bill $bill): View
    {
        Gate::authorize('update', $bill);

        return view('bills.edit', [
            'bill' => $bill,
            'categories' => $this->categories($request, $bill->category_id),
            'recurrences' => Bill::RECURRENCES,
            'statuses' => Bill::STATUSES,
        ]);
    }

    public function update(UpdateBillRequest $request, Bill $bill): RedirectResponse
    {
        $bill->update($request->validated());

        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        Gate::authorize('delete', $bill);
        $bill->delete();

        return redirect()->route('bills.index')->with('success', 'Tagihan berhasil diarsipkan.');
    }

    private function categories(Request $request, ?int $includeCategory = null): Collection
    {
        return $request->user()->categories()
            ->where(fn ($query) => $query->where('is_active', true)
                ->when($includeCategory, fn ($query, $id) => $query->orWhereKey($id)))
            ->orderBy('type')->orderBy('name')->get();
    }
}
