@extends('layouts.app')
@section('title', 'Anggaran')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker text-amber-700 dark:text-amber-400">Perencanaan bulanan</p>
            <h1 class="page-title">Anggaran</h1>
            <p class="page-description">Bandingkan target anggaran bulanan dengan realisasi pengeluaran riil.</p>
        </div>
        <a href="{{ route('budgets.create', ['month' => $month, 'year' => $year]) }}" class="btn-primary">
            <x-icon name="plus" size="4"/>
            <span>Tambah anggaran</span>
        </a>
    </header>

    <form method="GET" action="{{ route('budgets.index') }}" class="filter-surface mt-6 grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
        <div>
            <label for="month" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Bulan</label>
            <select id="month" name="month" class="form-control">
                @foreach(range(1, 12) as $value)
                    <option value="{{ $value }}" @selected($month === $value)>{{ \Carbon\CarbonImmutable::create(2000, $value)->locale('id')->translatedFormat('F') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="year" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Tahun</label>
            <input id="year" name="year" type="number" min="2000" max="9999" value="{{ $year }}" class="form-control">
        </div>
        <button class="btn-secondary">Terapkan</button>
        <a href="{{ route('budgets.index') }}" class="btn-ghost">Bulan ini</a>
    </form>

    @if($budgets->isEmpty())
        <x-empty-state class="mt-8" icon="budget" title="Belum ada anggaran bulan ini" description="Tambahkan target kategori pengeluaran untuk {{ \Carbon\CarbonImmutable::create(2000, $month)->locale('id')->translatedFormat('F') }} {{ $year }}." :action="route('budgets.create', ['month' => $month, 'year' => $year])" action-label="Tambah anggaran" accent="amber" />
    @else
        @php
            $pageBudgets = collect($budgets->items());
            $pageBudgetTarget = $pageBudgets->sum(fn ($item) => (float) $item->amount);
            $pageBudgetUsed = $pageBudgets->sum(fn ($item) => (float) $item->usedAmount());
            $attentionBudgetCount = $pageBudgets->filter(fn ($item) => $item->isOverBudget() || $item->utilizationPercentage() >= 80)->count();
        @endphp
        <section class="premium-surface accent-amber mt-8 grid overflow-hidden sm:grid-cols-3" aria-label="Ringkasan anggaran pada halaman ini">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-300">Target halaman</p>
                <p class="mt-2 break-words text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $preferences->currency }} {{ number_format($pageBudgetTarget, 2, '.', ',') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dari {{ $pageBudgets->count() }} anggaran</p>
            </div>
            <div class="border-t border-amber-100/80 p-5 dark:border-amber-900/50 sm:border-l sm:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">Terpakai</p>
                <p class="mt-2 break-words text-2xl font-bold tabular-nums text-violet-700 dark:text-violet-300">{{ $preferences->currency }} {{ number_format($pageBudgetUsed, 2, '.', ',') }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Realisasi halaman ini</p>
            </div>
            <div class="border-t border-amber-100/80 p-5 dark:border-amber-900/50 sm:border-l sm:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">Perlu perhatian</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-rose-700 dark:text-rose-300">{{ $attentionBudgetCount }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Mendekati atau melewati batas</p>
            </div>
        </section>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach($budgets as $budget)
                @php($percentage = $budget->utilizationPercentage())
                <article class="premium-surface p-5 transition hover:-translate-y-0.5 hover:shadow-md {{ $budget->isOverBudget() ? 'accent-rose' : ($percentage >= 80 ? 'accent-amber' : 'accent-emerald') }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <x-category-badge :category="$budget->category" class="font-bold text-slate-900 dark:text-white" />
                            <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                                Anggaran <span class="font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ $preferences->currency }} {{ number_format((float) $budget->amount, 2, '.', ',') }}</span>
                            </p>
                        </div>
                        <span class="shrink-0 {{ $budget->isOverBudget() ? 'status-chip-rose' : ($percentage >= 80 ? 'status-chip-amber' : 'status-chip-emerald') }}">
                            {{ $budget->isOverBudget() ? 'Melebihi anggaran' : ($percentage >= 80 ? 'Mendekati batas' : 'Normal') }}@if(! $budget->isOverBudget() && $percentage < 80)<span class="sr-only"> — Sesuai rencana</span>@endif
                        </span>
                    </div>
                    <div class="progress-track mt-4" role="progressbar" aria-valuenow="{{ min(100, round($percentage)) }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-fill {{ $budget->isOverBudget() ? 'bg-gradient-to-r from-rose-500 to-red-600' : ($percentage >= 80 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-emerald-500 to-teal-500') }}" style="width: {{ min(100, $percentage) }}%"></div>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                        <div class="rounded-xl border border-slate-100 bg-white/70 p-2.5 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-medium text-slate-400">Terpakai</p>
                            <p class="mt-1 font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format((float) $budget->usedAmount(), 2, '.', ',') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-white/70 p-2.5 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-medium text-slate-400">Tersisa</p>
                            <p class="mt-1 font-bold tabular-nums {{ $budget->isOverBudget() ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200' }}">{{ number_format((float) $budget->remainingAmount(), 2, '.', ',') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-white/70 p-2.5 text-right dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-medium text-slate-400">Progres</p>
                            <p class="mt-1 font-bold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format($percentage, 1) }}%</p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <a href="{{ route('budgets.edit', $budget) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Edit anggaran &rarr;</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $budgets->links() }}</div>
    @endif
</div>
@endsection
