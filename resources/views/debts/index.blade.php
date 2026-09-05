@extends('layouts.app')
@section('title', 'Utang & Piutang')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker text-rose-700 dark:text-rose-400">Saldo utang-piutang</p>
            <h1 class="page-title">Utang &amp; piutang</h1>
            <p class="page-description">Pantau kewajiban utang Anda serta piutang yang dipinjam pihak lain.</p>
        </div>
        <a href="{{ route('debts.create') }}" class="btn-primary">
            <x-icon name="plus" size="4"/>
            <span>Tambah catatan</span>
        </a>
    </header>

    <form method="GET" action="{{ route('debts.index') }}" class="filter-surface mt-6 grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
        <div>
            <label for="type" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Jenis</label>
            <select id="type" name="type" class="form-control">
                <option value="">Semua jenis</option>
                @foreach(App\Models\Debt::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">Semua status</option>
                @foreach(App\Models\Debt::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-secondary">Terapkan</button>
        <a href="{{ route('debts.index') }}" class="btn-ghost">Reset</a>
    </form>

    @if($debts->isEmpty())
        <x-empty-state class="mt-8" icon="debt" title="Catatan utang atau piutang tidak ditemukan" description="Buat catatan utang atau piutang pertama, atau sesuaikan filter." :action="route('debts.create')" action-label="Tambah catatan" accent="rose" />
    @else
        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach($debts as $debt)
                @php($status = $debt->effectiveStatus())
                @php($paidPercentage = (float) $debt->original_amount > 0 ? (1 - ((float) $debt->remaining_amount / (float) $debt->original_amount)) * 100 : 0)
                <a href="{{ route('debts.show', $debt) }}" class="premium-surface p-5 transition hover:-translate-y-0.5 hover:shadow-md {{ $debt->type === 'debt' ? 'accent-rose' : 'accent-cyan' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span class="{{ $debt->type === 'debt' ? 'icon-badge-rose' : 'icon-badge-cyan' }}">
                                <x-icon name="debt" size="5"/>
                            </span>
                            <div class="min-w-0">
                                <span class="text-xs font-bold uppercase tracking-wider {{ $debt->type === 'debt' ? 'text-rose-700 dark:text-rose-400' : 'text-cyan-700 dark:text-cyan-300' }}">
                                    {{ App\Models\Debt::TYPES[$debt->type] }}
                                </span>
                                <h2 class="mt-0.5 truncate font-bold text-slate-900 dark:text-white">{{ $debt->person_name }}</h2>
                            </div>
                        </div>
                        <span class="shrink-0 {{ $status === 'paid' ? 'status-chip-emerald' : ($status === 'overdue' ? 'status-chip-rose' : 'status-chip-amber') }}">
                            {{ App\Models\Debt::STATUSES[$status] }}
                        </span>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-100 bg-white/70 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-medium text-slate-400">Tersisa</p>
                            <p class="mt-1 break-words font-bold tabular-nums text-slate-900 dark:text-white">
                                {{ $preferences->currency }} {{ number_format((float) $debt->remaining_amount, 2, '.', ',') }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">dari {{ number_format((float) $debt->original_amount, 2, '.', ',') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-white/70 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-xs font-medium text-slate-400">Jatuh tempo</p>
                            <p class="mt-1 font-bold text-slate-800 dark:text-slate-200">
                                {{ $preferences->formatDate($debt->due_date) ?? 'Tanpa jatuh tempo' }}
                            </p>
                        </div>
                    </div>

                    <div class="progress-track mt-4">
                        <div class="progress-fill {{ $debt->type === 'debt' ? 'bg-gradient-to-r from-rose-500 to-red-600' : 'bg-gradient-to-r from-cyan-500 to-teal-500' }}" style="width: {{ min(100, max(0, $paidPercentage)) }}%"></div>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $debts->links() }}</div>
    @endif
</div>
@endsection
