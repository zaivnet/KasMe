@extends('layouts.app')
@section('title', $account->name)
@section('content')
<div class="mx-auto max-w-4xl">
    <a href="{{ route('accounts.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Semua akun</a>
    @php
        $typeMeta = [
            'bank' => ['surface' => 'accent-blue', 'text' => 'text-blue-900 dark:text-blue-200', 'color' => '#2563eb'],
            'cash' => ['surface' => 'accent-emerald', 'text' => 'text-emerald-900 dark:text-emerald-200', 'color' => '#059669'],
            'ewallet' => ['surface' => 'accent-violet', 'text' => 'text-violet-900 dark:text-violet-200', 'color' => '#7c3aed'],
            'savings' => ['surface' => 'accent-amber', 'text' => 'text-amber-900 dark:text-amber-200', 'color' => '#d97706'],
            'credit_card' => ['surface' => 'accent-rose', 'text' => 'text-rose-900 dark:text-rose-200', 'color' => '#e11d48'],
            'other' => ['surface' => 'accent-slate', 'text' => 'text-slate-900 dark:text-slate-200', 'color' => '#475569'],
        ];
        $meta = $typeMeta[$account->type] ?? $typeMeta['other'];
        $color = $account->color ?: $meta['color'];
    @endphp
    <section class="section-card {{ $meta['surface'] }} mt-4">
        <span class="absolute inset-y-0 left-0 w-1.5" style="background-color: {{ $color }}"></span>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3.5">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-white shadow-sm" style="background-color: {{ $color }}">
                    <x-account-icon :icon="$account->icon" :type="$account->type" size="7" />
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $account->name }}</h1>
                        <span class="{{ $account->is_active ? 'status-chip-emerald' : 'status-chip-muted' }}">
                            {{ $account->is_active ? 'Aktif' : 'Tidak aktif' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">{{ \App\Models\Account::TYPES[$account->type] }} · Mata uang {{ $account->currency }}</p>
                </div>
            </div>
            <a href="{{ route('accounts.edit', $account) }}" class="btn-secondary">Edit akun</a>
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200/80 bg-white/80 p-5 dark:border-slate-800 dark:bg-slate-950/60">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Saldo terhitung</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums {{ $meta['text'] }} sm:text-4xl">{{ $account->currency }} {{ number_format((float) $balance, 2, '.', ',') }}</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Saldo dihitung dari seluruh catatan keuangan, termasuk saldo awal dan pergerakan dana.</p>
        </div>

        <dl class="mt-6 grid gap-4 border-t border-slate-200/80 pt-6 text-sm dark:border-slate-800 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-950/40">
                <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">Saldo awal</dt>
                <dd class="mt-1 font-bold tabular-nums text-slate-900 dark:text-white">{{ $account->currency }} {{ number_format((float) $account->opening_balance, 2, '.', ',') }}</dd>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 dark:border-slate-800 dark:bg-slate-950/40">
                <dt class="text-xs font-medium text-slate-500 dark:text-slate-400">Warna aksen</dt>
                <dd class="mt-1 flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                    @if($account->color)
                        <span class="size-4 rounded-full border border-slate-300 shadow-xs" style="background-color: {{ $account->color }}"></span>
                        <span>{{ $account->color }}</span>
                    @else
                        <span class="text-slate-400 font-normal">Belum diatur</span>
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Arsipkan akun</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Akun akan diarsipkan dan disembunyikan dari daftar aktif. Catatan keuangan terkait tetap aman.</p>
        <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="mt-4" onsubmit="return confirm('Arsipkan akun ini? Catatannya akan tetap tersimpan.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Arsipkan akun</button>
        </form>
    </section>
</div>
@endsection
