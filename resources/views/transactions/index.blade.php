@extends('layouts.app')
@section('title', 'Transaksi')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Buku kas keuangan</p>
            <h1 class="page-title">Transaksi</h1>
            <p class="page-description">Pemasukan, pengeluaran, dan penyesuaian saldo kas keuangan Anda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('transactions.export', request()->query()) }}" class="btn-secondary">
                <x-icon name="report" size="4"/>
                <span>Ekspor CSV</span>
            </a>
            <a href="{{ route('transactions.create') }}" class="btn-primary">
                <x-icon name="plus" size="4"/>
                <span>Tambah transaksi</span>
            </a>
        </div>
    </header>

    <form method="GET" class="filter-surface mt-6 grid gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="date_from">Dari</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="form-control text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="date_to">Sampai</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="form-control text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="filter_account">Akun</label>
            <select id="filter_account" name="account_id" class="form-control text-sm">
                <option value="">Semua akun</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string)($filters['account_id'] ?? '') === (string)$account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="filter_category">Kategori</label>
            <select id="filter_category" name="category_id" class="form-control text-sm">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300" for="filter_type">Jenis</label>
            <select id="filter_type" name="type" class="form-control text-sm">
                <option value="">Semua jenis</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="btn-secondary flex-1">Filter</button>
            <a href="{{ route('transactions.index') }}" class="btn-ghost px-2">Reset</a>
        </div>
    </form>

    @if($transactions->isEmpty())
        <x-empty-state class="mt-8" icon="transaction" title="Transaksi tidak ditemukan" description="Catat pemasukan, pengeluaran, atau penyesuaian, atau ubah filter pencarian." :action="route('transactions.create')" action-label="Tambah transaksi" accent="violet" />
    @else
        <div class="premium-surface mt-8 overflow-hidden">
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($transactions as $transaction)
                    @php
                        $isPositive = $transaction->type === 'income' || $transaction->adjustment_direction === 'increase';
                        $catColor = $transaction->category?->color ?: ($isPositive ? '#059669' : '#e11d48');
                    @endphp
                    <a href="{{ route('transactions.show', $transaction) }}" class="ledger-row sm:grid-cols-[7.5rem_1fr_auto] sm:items-center">
                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                            {{ $preferences->formatDate($transaction->transaction_date) }}
                        </div>
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl text-white shadow-xs" style="background-color: {{ $catColor }}">
                                <x-icon :name="$transaction->category?->icon ?: 'transaction'" size="5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate font-bold text-slate-900 dark:text-white">{{ $transaction->description ?: App\Models\Transaction::TYPES[$transaction->type] }}</h2>
                                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                                    <span class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $transaction->account->name }}</span>
                                    <x-category-badge :category="$transaction->category" :fallback="$transaction->adjustment_direction ? App\Models\Transaction::ADJUSTMENT_DIRECTIONS[$transaction->adjustment_direction] : 'Tanpa kategori'" class="text-xs" />
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-bold tabular-nums {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $isPositive ? '+' : '-' }} {{ $transaction->account->currency }} {{ number_format((float)$transaction->amount, 2, '.', ',') }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="mt-8">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
