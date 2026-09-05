@extends('layouts.app')
@section('title', 'Tagihan')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker text-amber-700 dark:text-amber-400">Kewajiban rutin</p>
            <h1 class="page-title">Tagihan</h1>
            <p class="page-description">Pantau jadwal tanggal jatuh tempo, periode pengulangan, dan status pembayaran.</p>
        </div>
        <a href="{{ route('bills.create') }}" class="btn-primary">
            <x-icon name="plus" size="4"/>
            <span>Tambah tagihan</span>
        </a>
    </header>

    <form method="GET" action="{{ route('bills.index') }}" class="filter-surface mt-6 grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
        <div>
            <label for="status" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">Semua status</option>
                @foreach(App\Models\Bill::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="recurrence" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Pengulangan</label>
            <select id="recurrence" name="recurrence" class="form-control">
                <option value="">Semua pengulangan</option>
                @foreach(App\Models\Bill::RECURRENCES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['recurrence'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <input type="checkbox" name="upcoming" value="1" @checked($filters['upcoming'] ?? false) class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <span>30 hari ke depan</span>
        </label>
        <div class="flex gap-2">
            <button class="btn-secondary">Terapkan</button>
            <a href="{{ route('bills.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    @if($bills->isEmpty())
        <x-empty-state class="mt-8" icon="bill" title="Tagihan tidak ditemukan" description="Tambahkan kewajiban pertama atau sesuaikan filter." :action="route('bills.create')" action-label="Tambah tagihan" accent="amber" />
    @else
        @php
            $pageBills = collect($bills->items());
            $pageBillTotal = $pageBills->sum(fn ($item) => (float) $item->amount);
            $overdueBillCount = $pageBills->filter(fn ($item) => $item->effectiveStatus() === 'overdue')->count();
            $paidBillCount = $pageBills->filter(fn ($item) => $item->effectiveStatus() === 'paid')->count();
        @endphp
        <section class="premium-surface accent-amber mt-8 grid overflow-hidden sm:grid-cols-3" aria-label="Ringkasan tagihan pada halaman ini">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">Nominal halaman</p>
                <p class="mt-2 break-words text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $preferences->currency }} {{ number_format($pageBillTotal, 2, '.', ',') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dari {{ $pageBills->count() }} tagihan</p>
            </div>
            <div class="border-t border-amber-100/80 p-5 dark:border-amber-900/50 sm:border-l sm:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">Terlambat</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-rose-700 dark:text-rose-300">{{ $overdueBillCount }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Perlu ditindaklanjuti</p>
            </div>
            <div class="border-t border-amber-100/80 p-5 dark:border-amber-900/50 sm:border-l sm:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Lunas</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $paidBillCount }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sudah ditandai selesai</p>
            </div>
        </section>

        <div class="premium-surface mt-6 overflow-hidden">
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($bills as $bill)
                    @php($effectiveStatus = $bill->effectiveStatus())
                    <a href="{{ route('bills.edit', $bill) }}" class="ledger-row sm:grid-cols-[7.5rem_1fr_auto] sm:items-center">
                        <div>
                            <p class="text-xs font-medium text-slate-400">Tanggal jatuh tempo</p>
                            <p class="mt-0.5 text-xs font-bold tabular-nums {{ $effectiveStatus === 'overdue' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200' }}">
                                {{ $preferences->formatDate($bill->due_date) }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate font-bold text-slate-900 dark:text-white">{{ $bill->name }}</h2>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <x-category-badge :category="$bill->category"/>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ App\Models\Bill::RECURRENCES[$bill->recurrence] }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:block sm:text-right">
                            <p class="font-bold tabular-nums text-slate-900 dark:text-white sm:text-base">
                                {{ $preferences->currency }} {{ number_format((float) $bill->amount, 2, '.', ',') }}
                            </p>
                            <span class="mt-1 {{ $effectiveStatus === 'paid' ? 'status-chip-emerald' : ($effectiveStatus === 'overdue' ? 'status-chip-rose' : 'status-chip-amber') }}">
                                {{ App\Models\Bill::STATUSES[$effectiveStatus] }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="mt-8">{{ $bills->links() }}</div>
    @endif
</div>
@endsection
