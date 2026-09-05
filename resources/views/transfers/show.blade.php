@extends('layouts.app')
@section('title', 'Detail Transfer')
@section('content')
<div class="mx-auto max-w-4xl">
    <a href="{{ route('transfers.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Riwayat transfer</a>
    <section class="section-card accent-cyan mt-4">
        <span class="absolute inset-y-0 left-0 w-1.5 bg-teal-600"></span>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $preferences->formatDate($transfer->transfer_date) }}</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    <span class="text-teal-700 dark:text-teal-300">{{ $transfer->fromAccount->name }}</span>
                    <span class="text-slate-400 mx-2">&rarr;</span>
                    <span class="text-violet-700 dark:text-violet-300">{{ $transfer->toAccount->name }}</span>
                </h1>
            </div>
            <a href="{{ route('transfers.edit', $transfer) }}" class="btn-secondary">Edit transfer</a>
        </div>

        <dl class="mt-8 grid gap-4 border-t border-slate-100 pt-6 sm:grid-cols-2 dark:border-slate-800">
            <div class="rounded-xl border border-slate-100 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                <dt class="text-xs font-medium text-slate-400">Nominal transfer</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-teal-700 dark:text-teal-300">
                    {{ $transfer->fromAccount->currency }} {{ number_format((float)$transfer->amount, 2, '.', ',') }}
                </dd>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                <dt class="text-xs font-medium text-slate-400">Biaya transfer</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-slate-800 dark:text-slate-200">
                    {{ $transfer->fromAccount->currency }} {{ number_format((float)$transfer->fee, 2, '.', ',') }}
                </dd>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white/70 p-4 dark:border-slate-800 dark:bg-slate-950/40 sm:col-span-2">
                <dt class="text-xs font-medium text-slate-400">Deskripsi</dt>
                <dd class="mt-1 whitespace-pre-line font-medium text-slate-800 dark:text-slate-200">{{ $transfer->description ?: 'Tanpa deskripsi' }}</dd>
            </div>
        </dl>
    </section>

    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Batalkan transfer</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Catatan transfer utama akan dihapus secara lunak dan dampak mutasi pada kedua akun akan dibatalkan secara atomik.</p>
        <form method="POST" action="{{ route('transfers.destroy', $transfer) }}" class="mt-4" onsubmit="return confirm('Batalkan transfer ini? Dampaknya akan dihapus dari kedua akun.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Batalkan transfer</button>
        </form>
    </section>
</div>
@endsection
