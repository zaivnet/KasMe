@extends('layouts.app')
@section('title', 'Target Tabungan')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker text-violet-700 dark:text-violet-400">Dana tersimpan</p>
            <h1 class="page-title">Target tabungan</h1>
            <p class="page-description">Kumpulkan dan alokasikan saldo menuju target tabungan impian Anda.</p>
        </div>
        <a href="{{ route('saving-goals.create') }}" class="btn-primary">
            <x-icon name="plus" size="4"/>
            <span>Tambah target</span>
        </a>
    </header>

    <form method="GET" action="{{ route('saving-goals.index') }}" class="filter-surface mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="status" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">Semua status</option>
                @foreach(App\Models\SavingGoal::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn-secondary">Terapkan</button>
            <a href="{{ route('saving-goals.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    @if($goals->isEmpty())
        <x-empty-state class="mt-8" icon="goal" title="Target tabungan tidak ditemukan" description="Buat target pertama atau sesuaikan filter." :action="route('saving-goals.create')" action-label="Tambah target" accent="violet" />
    @else
        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach($goals as $goal)
                @php($percentage = $goal->progressPercentage())
                @php($status = $goal->effectiveStatus())
                <a href="{{ route('saving-goals.show', $goal) }}" class="premium-surface accent-violet p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span class="icon-badge-violet">
                                <x-icon name="goal" size="5"/>
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate font-bold text-slate-900 dark:text-white">{{ $goal->name }}</h2>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Target {{ $preferences->formatDate($goal->target_date) ?? 'tanpa tenggat' }}</p>
                            </div>
                        </div>
                        <span class="h-fit shrink-0 {{ $status === 'completed' ? 'status-chip-emerald' : ($status === 'cancelled' ? 'status-chip-muted' : 'status-chip-violet') }}">
                            {{ App\Models\SavingGoal::STATUSES[$status] }}
                        </span>
                    </div>

                    <div class="progress-track mt-5">
                        <div class="progress-fill bg-gradient-to-r from-violet-600 via-teal-500 to-emerald-500" style="width: {{ min(100, $percentage) }}%"></div>
                    </div>

                    <div class="mt-3 flex flex-col justify-between gap-1 text-sm sm:flex-row">
                        <span class="font-bold tabular-nums text-violet-700 dark:text-violet-300">
                            {{ $preferences->currency }} {{ number_format((float) $goal->progressAmount(), 2, '.', ',') }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                            {{ number_format($percentage, 1) }}% dari {{ number_format((float) $goal->target_amount, 2, '.', ',') }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $goals->links() }}</div>
    @endif
</div>
@endsection
