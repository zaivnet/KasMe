@extends('layouts.app')
@section('title', $goal->name)
@section('content')
@php($percentage = $goal->progressPercentage())
@php($status = $goal->effectiveStatus())
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('saving-goals.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke target tabungan</a>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $goal->name }}</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Target {{ $preferences->formatDate($goal->target_date) ?? 'tanpa tenggat' }}</p>
        </div>
        <a href="{{ route('saving-goals.edit', $goal) }}" class="btn-secondary">Edit target</a>
    </header>

    <section class="section-card accent-violet mt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-medium text-slate-400">Progres tabungan terkumpul</p>
                <p class="mt-1 text-3xl font-extrabold tracking-tight tabular-nums text-violet-700 dark:text-violet-300 sm:text-4xl">
                    {{ $preferences->currency }} {{ number_format((float) $goal->progressAmount(), 2, '.', ',') }}
                </p>
            </div>
            <div class="sm:text-right">
                <span class="{{ $status === 'completed' ? 'status-chip-emerald' : ($status === 'cancelled' ? 'status-chip-muted' : 'status-chip-violet') }}">
                    {{ App\Models\SavingGoal::STATUSES[$status] }}
                </span>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    dari {{ $preferences->currency }} {{ number_format((float) $goal->target_amount, 2, '.', ',') }}
                </p>
            </div>
        </div>

        <div class="progress-track mt-5">
            <div class="progress-fill bg-gradient-to-r from-violet-600 via-teal-500 to-emerald-500" style="width: {{ min(100, $percentage) }}%"></div>
        </div>
        <p class="mt-2 text-right text-xs font-bold tabular-nums text-slate-600 dark:text-slate-300">{{ number_format($percentage, 1) }}%</p>

        @if($goal->description)
            <p class="mt-5 whitespace-pre-line border-t border-slate-100 pt-4 text-xs leading-relaxed text-slate-600 dark:border-slate-800 dark:text-slate-300 sm:text-sm">
                {{ $goal->description }}
            </p>
        @endif
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <section class="section-card">
            <div class="section-heading">
                <span class="icon-badge-violet"><x-icon name="transaction" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Riwayat pergerakan</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Daftar mutasi simpanan dana pada target ini.</p>
                </div>
            </div>
            @if($goal->transactions->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    Belum ada kontribusi atau penarikan.
                </div>
            @else
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($goal->transactions as $transaction)
                        <a href="{{ route('saving-goals.transactions.edit', [$goal, $transaction]) }}" class="grid gap-1 py-3.5 sm:grid-cols-[7.5rem_1fr_auto] sm:items-center">
                            <p class="text-xs font-medium text-slate-400">{{ $preferences->formatDate($transaction->transaction_date) }}</p>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">{{ $transaction->account->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ App\Models\SavingGoalTransaction::TYPES[$transaction->type] }}</p>
                            </div>
                            <p class="font-bold tabular-nums sm:text-right {{ $transaction->type === 'contribution' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $transaction->type === 'contribution' ? '-' : '+' }} {{ $preferences->currency }} {{ number_format((float) $transaction->amount, 2, '.', ',') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="section-card accent-emerald">
            <div class="section-heading">
                <span class="icon-badge-emerald"><x-icon name="plus" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Pindahkan dana</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Kontribusi menyimpan dana dari akun; penarikan mengembalikannya.</p>
                </div>
            </div>
            @if($accounts->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700">
                    Buat akun aktif terlebih dahulu.
                </div>
            @else
                <form method="POST" action="{{ route('saving-goals.transactions.store', $goal) }}" class="mt-5">
                    @csrf
                    @include('saving-goals.transactions._form', ['transaction' => null])
                    <button class="btn-primary mt-5 w-full">Catat pergerakan</button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection
