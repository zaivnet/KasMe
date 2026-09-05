@extends('layouts.app')
@section('title', $debt->person_name)
@section('content')
@php($status = $debt->effectiveStatus())
<div class="mx-auto max-w-6xl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('debts.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke catatan</a>
            <p class="mt-3 text-xs font-bold uppercase tracking-wider {{ $debt->type === 'debt' ? 'text-rose-700 dark:text-rose-400' : 'text-teal-700 dark:text-teal-400' }}">{{ App\Models\Debt::TYPES[$debt->type] }}</p>
            <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $debt->person_name }}</h1>
        </div>
        <a href="{{ route('debts.edit', $debt) }}" class="btn-secondary">Edit catatan</a>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="premium-surface p-5 {{ $debt->type === 'debt' ? 'accent-rose' : 'accent-cyan' }}">
            <p class="text-xs font-medium text-slate-400">Jumlah awal</p>
            <p class="mt-1.5 text-xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $preferences->currency }} {{ number_format((float) $debt->original_amount, 2, '.', ',') }}</p>
        </article>
        <article class="premium-surface p-5 {{ $debt->type === 'debt' ? 'accent-rose' : 'accent-cyan' }}">
            <p class="text-xs font-medium text-slate-400">Tersisa</p>
            <p class="mt-1.5 text-xl font-bold tabular-nums {{ $debt->type === 'debt' ? 'text-rose-700 dark:text-rose-400' : 'text-teal-700 dark:text-teal-300' }}">{{ $preferences->currency }} {{ number_format((float) $debt->remaining_amount, 2, '.', ',') }}</p>
        </article>
        <article class="premium-surface p-5">
            <p class="text-xs font-medium text-slate-400">Tanggal jatuh tempo</p>
            <p class="mt-1.5 text-lg font-bold text-slate-800 dark:text-slate-200">{{ $preferences->formatDate($debt->due_date) ?? 'Tidak ada' }}</p>
        </article>
        <article class="premium-surface p-5">
            <p class="text-xs font-medium text-slate-400">Status</p>
            <p class="mt-1.5">
                <span class="{{ $status === 'paid' ? 'status-chip-emerald' : ($status === 'overdue' ? 'status-chip-rose' : 'status-chip-amber') }}">
                    {{ App\Models\Debt::STATUSES[$status] }}
                </span>
            </p>
        </article>
    </section>

    @if($debt->notes)
        <section class="section-card mt-6">
            <h2 class="font-bold text-slate-900 dark:text-white">Catatan</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $debt->notes }}</p>
        </section>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <section class="section-card">
            <div class="section-heading">
                <span class="icon-badge-violet"><x-icon name="transaction" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Riwayat pembayaran</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Setiap pembayaran langsung memengaruhi saldo akun terpilih.</p>
                </div>
            </div>
            @if($debt->payments->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">Belum ada pembayaran.</div>
            @else
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($debt->payments as $payment)
                        <a href="{{ route('debts.payments.edit', [$debt, $payment]) }}" class="grid gap-1 py-3.5 sm:grid-cols-[7.5rem_1fr_auto] sm:items-center">
                            <p class="text-xs font-medium text-slate-400">{{ $preferences->formatDate($payment->payment_date) }}</p>
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">{{ $payment->account->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payment->notes ?: 'Tanpa catatan' }}</p>
                            </div>
                            <p class="font-bold tabular-nums sm:text-right {{ $debt->type === 'debt' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $debt->type === 'debt' ? '-' : '+' }} {{ $preferences->currency }} {{ number_format((float) $payment->amount, 2, '.', ',') }}
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
                    <h2 class="font-bold text-slate-900 dark:text-white">Catat pembayaran</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $debt->type === 'debt' ? 'Mengurangi saldo akun yang dipilih.' : 'Menambah saldo akun yang dipilih.' }}</p>
                </div>
            </div>
            @if((float) $debt->remaining_amount === 0.0)
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300">
                    Catatan ini sudah lunas sepenuhnya.
                </div>
            @elseif($accounts->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700">
                    Buat akun aktif terlebih dahulu sebelum mencatat pembayaran.
                </div>
            @else
                <form method="POST" action="{{ route('debts.payments.store', $debt) }}" class="mt-5">
                    @csrf
                    @include('debts.payments._form', ['payment' => null])
                    <button class="btn-primary mt-5 w-full">Catat pembayaran</button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection
