<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\DeleteTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', $request->user()->id)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)],
            'type' => ['nullable', Rule::in(array_keys(Transaction::TYPES))],
        ]);

        $transactions = $request->user()->transactions()->with(['account', 'category'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', '<=', $date))
            ->when($filters['account_id'] ?? null, fn ($query, $id) => $query->where('account_id', $id))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'accounts' => $request->user()->accounts()->orderBy('name')->get(),
            'categories' => $request->user()->categories()->orderBy('name')->get(),
            'types' => Transaction::TYPES,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Transaction::class);

        return view('transactions.create', $this->formOptions($request));
    }

    public function store(StoreTransactionRequest $request, CreateTransaction $action): RedirectResponse
    {
        $transaction = $action->handle($request->user(), $request->validated());

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi berhasil dibuat.');
    }

    public function show(Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        return view('transactions.show', compact('transaction'));
    }

    public function edit(Request $request, Transaction $transaction): View
    {
        Gate::authorize('update', $transaction);

        return view('transactions.edit', array_merge($this->formOptions($request, $transaction), compact('transaction')));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransaction $action): RedirectResponse
    {
        $action->handle($transaction, $request->validated());

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction, DeleteTransaction $action): RedirectResponse
    {
        Gate::authorize('delete', $transaction);
        $action->handle($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    public function attachment(Transaction $transaction): StreamedResponse
    {
        Gate::authorize('view', $transaction);
        abort_unless($transaction->attachment && Storage::disk('local')->exists($transaction->attachment), 404);

        return Storage::disk('local')->download(
            $transaction->attachment,
            'lampiran-transaksi-'.$transaction->id.'.'.pathinfo($transaction->attachment, PATHINFO_EXTENSION)
        );
    }

    private function formOptions(Request $request, ?Transaction $transaction = null): array
    {
        return [
            'accounts' => $request->user()->accounts()->orderBy('name')->get(),
            'categories' => $request->user()->categories()
                ->where(fn ($query) => $query->where('is_active', true)
                    ->when($transaction?->category_id, fn ($query, $id) => $query->orWhereKey($id)))
                ->orderBy('type')->orderBy('name')->get(),
            'types' => Transaction::TYPES,
            'directions' => Transaction::ADJUSTMENT_DIRECTIONS,
        ];
    }
}
