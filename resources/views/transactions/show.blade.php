@extends('layouts.app')
@section('title', 'Detail Transaksi')
@section('content')
<div class="mx-auto max-w-4xl">
    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Semua transaksi</a>
    @php
        $isPositive = $transaction->type === 'income' || $transaction->adjustment_direction === 'increase';
    @endphp
    <section class="section-card {{ $isPositive ? 'accent-emerald' : 'accent-rose' }} mt-4">
        <span class="absolute inset-y-0 left-0 w-1.5 {{ $isPositive ? 'bg-emerald-600' : 'bg-rose-600' }}"></span>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <span class="{{ $isPositive ? 'status-chip-emerald' : 'status-chip-rose' }}">
                    {{ App\Models\Transaction::TYPES[$transaction->type] }}{{ $transaction->adjustment_direction ? ' · '.App\Models\Transaction::ADJUSTMENT_DIRECTIONS[$transaction->adjustment_direction] : '' }}
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight tabular-nums {{ $isPositive ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }} sm:text-4xl">
                    {{ $isPositive ? '+' : '-' }} {{ $transaction->account->currency }} {{ number_format((float) $transaction->amount, 2, '.', ',') }}
                </h1>
                <p class="mt-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">{{ $preferences->formatDate($transaction->transaction_date) }}</p>
            </div>
            <a href="{{ route('transactions.edit', $transaction) }}" class="btn-secondary">Edit transaksi</a>
        </div>

        <dl class="mt-8 grid gap-4 border-t border-slate-100 pt-6 text-sm dark:border-slate-800 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-white/70 p-3.5 dark:border-slate-800 dark:bg-slate-950/40">
                <dt class="text-xs font-medium text-slate-400">Akun sumber</dt>
                <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $transaction->account->name }}</dd>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white/70 p-3.5 dark:border-slate-800 dark:bg-slate-950/40">
                <dt class="text-xs font-medium text-slate-400">Kategori</dt>
                <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $transaction->category?->name ?? 'Tidak berlaku' }}</dd>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white/70 p-3.5 dark:border-slate-800 dark:bg-slate-950/40 sm:col-span-2">
                <dt class="text-xs font-medium text-slate-400">Deskripsi</dt>
                <dd class="mt-1 whitespace-pre-line font-medium text-slate-800 dark:text-slate-200">{{ $transaction->description ?: 'Tanpa deskripsi' }}</dd>
            </div>
        </dl>

        @if($transaction->attachment)
            <div class="mt-6 border-t border-slate-100 pt-6 dark:border-slate-800">
                <p class="text-xs font-medium text-slate-400">Berkas lampiran</p>
                <a href="{{ route('transactions.attachment', $transaction) }}" class="btn-secondary mt-2">
                    <x-icon name="report" size="4" />
                    <span>Unduh lampiran privat</span>
                </a>
            </div>
        @endif
    </section>

    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Hapus transaksi</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Catatan transaksi akan dihapus secara lunak dan dampak mutasi saldo akun dibatalkan.</p>
        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" class="mt-4" onsubmit="return confirm('Hapus transaksi ini? Dampaknya pada saldo akan dibatalkan.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Hapus transaksi</button>
        </form>
    </section>
</div>
@endsection
