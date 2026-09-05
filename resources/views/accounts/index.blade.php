@extends('layouts.app')
@section('title', 'Akun')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Akun keuangan</p>
            <h1 class="page-title">Akun</h1>
            <p class="page-description">Kelola rekening bank, dompet digital, uang tunai, dan tabungan Anda.</p>
        </div>
        <a href="{{ route('accounts.create') }}" class="btn-primary">
            <x-icon name="plus" size="4"/>
            <span>Tambah akun</span>
        </a>
    </header>

    @if ($accounts->isEmpty())
        <x-empty-state class="mt-8" icon="wallet" title="Belum ada akun" description="Tambahkan akun keuangan pertama untuk menetapkan saldo awal." :action="route('accounts.create')" action-label="Tambah akun" accent="cyan" />
    @else
        @php
            $pageAccounts = collect($accounts->items());
            $activeAccountCount = $pageAccounts->where('is_active', true)->count();
            $accountTypeCount = $pageAccounts->pluck('type')->unique()->count();
            $typeMeta = [
                'bank' => ['surface' => 'accent-blue', 'text' => 'text-blue-900 dark:text-blue-200', 'accent' => '#2563eb'],
                'cash' => ['surface' => 'accent-emerald', 'text' => 'text-emerald-900 dark:text-emerald-200', 'accent' => '#059669'],
                'ewallet' => ['surface' => 'accent-violet', 'text' => 'text-violet-900 dark:text-violet-200', 'accent' => '#7c3aed'],
                'savings' => ['surface' => 'accent-amber', 'text' => 'text-amber-900 dark:text-amber-200', 'accent' => '#d97706'],
                'credit_card' => ['surface' => 'accent-rose', 'text' => 'text-rose-900 dark:text-rose-200', 'accent' => '#e11d48'],
                'other' => ['surface' => 'accent-slate', 'text' => 'text-slate-900 dark:text-slate-200', 'accent' => '#475569'],
            ];
        @endphp
        <section class="premium-surface accent-cyan mt-8 grid overflow-hidden sm:grid-cols-3" aria-label="Ringkasan akun pada halaman ini">
            <div class="p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-cyan-700 dark:text-cyan-300">Ditampilkan</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $pageAccounts->count() }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Akun pada halaman ini</p>
            </div>
            <div class="border-t border-cyan-100/80 p-5 dark:border-cyan-900/50 sm:border-l sm:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Siap digunakan</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $activeAccountCount }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Akun aktif</p>
            </div>
            <div class="border-t border-cyan-100/80 p-5 dark:border-cyan-900/50 sm:border-l sm:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-300">Keragaman</p>
                <p class="mt-2 text-2xl font-bold tabular-nums text-violet-700 dark:text-violet-300">{{ $accountTypeCount }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Jenis akun</p>
            </div>
        </section>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($accounts as $account)
                @php
                    $meta = $typeMeta[$account->type] ?? $typeMeta['other'];
                    $color = $account->color ?: $meta['accent'];
                @endphp
                <a href="{{ route('accounts.show', $account) }}" class="premium-surface {{ $meta['surface'] }} group relative flex flex-col justify-between overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="absolute inset-y-0 left-0 w-1.5" style="background-color: {{ $color }}"></span>
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl text-white shadow-xs" style="background-color: {{ $color }}">
                                    <x-account-icon :icon="$account->icon" :type="$account->type" size="6" />
                                </span>
                                <div class="min-w-0">
                                    <h2 class="truncate font-bold text-slate-900 dark:text-white">{{ $account->name }}</h2>
                                    <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ \App\Models\Account::TYPES[$account->type] }}</p>
                                </div>
                            </div>
                            <span class="{{ $account->is_active ? 'status-chip-emerald' : 'status-chip-muted' }}">
                                {{ $account->is_active ? 'Aktif' : 'Tidak aktif' }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-6 pt-3 border-t border-slate-100/80 dark:border-slate-800/60">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Saldo saat ini</p>
                        <p class="mt-1 break-words text-xl font-bold tracking-tight tabular-nums {{ $meta['text'] }}">{{ $account->currency }} {{ number_format((float) $balances[$account->id], 2, '.', ',') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $accounts->links() }}</div>
    @endif
</div>
@endsection
