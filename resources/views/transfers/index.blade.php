@extends('layouts.app')
@section('title', 'Transfer')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker text-teal-700 dark:text-teal-400">Pergerakan antar akun</p>
            <h1 class="page-title">Transfer</h1>
            <p class="page-description">Pergerakan dana atomik antar akun keuangan Anda.</p>
        </div>
        <a href="{{ route('transfers.create') }}" class="btn-primary">
            <x-icon name="transfer" size="4"/>
            <span>Transfer baru</span>
        </a>
    </header>

    @if($transfers->isEmpty())
        <x-empty-state class="mt-8" icon="transfer" title="Belum ada transfer" description="Pindahkan dana antar akun ketika diperlukan." :action="route('transfers.create')" action-label="Transfer baru" accent="cyan" />
    @else
        <div class="premium-surface mt-8 overflow-hidden">
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($transfers as $transfer)
                    <a href="{{ route('transfers.show', $transfer) }}" class="ledger-row sm:grid-cols-[7.5rem_1fr_auto] sm:items-center">
                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400">
                            {{ $preferences->formatDate($transfer->transfer_date) }}
                        </div>
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span class="icon-badge-cyan">
                                <x-icon name="transfer" size="5"/>
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate font-bold text-slate-900 dark:text-white">
                                    <span class="text-teal-700 dark:text-teal-300">{{ $transfer->fromAccount->name }}</span>
                                    <span class="text-slate-400 mx-1">&rarr;</span>
                                    <span class="text-violet-700 dark:text-violet-300">{{ $transfer->toAccount->name }}</span>
                                </h2>
                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                    {{ $transfer->description ?: 'Transfer antar akun' }}
                                </p>
                            </div>
                        </div>
                        <div class="font-bold tabular-nums sm:text-right">
                            <span class="text-base text-teal-700 dark:text-teal-300">
                                {{ $transfer->fromAccount->currency }} {{ number_format((float)$transfer->amount, 2, '.', ',') }}
                            </span>
                            @if((float)$transfer->fee > 0)
                                <p class="mt-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                    Biaya {{ number_format((float)$transfer->fee, 2, '.', ',') }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="mt-8">{{ $transfers->links() }}</div>
    @endif
</div>
@endsection
