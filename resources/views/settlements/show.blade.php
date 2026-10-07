@extends('layouts.app')
@section('title', 'Detail Setoran Kas — ' . $settlement->periodLabel())
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('settlements.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke Riwayat Setoran</a>

    <header class="mt-4">
        <p class="section-kicker">Bukti pertanggungjawaban kas</p>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="page-title">Setoran Kas {{ $settlement->periodLabel() }}</h1>
            @if($settlement->isVoided())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <span>Status: Dibatalkan (Void)</span>
                </span>
            @endif
        </div>
        <p class="page-description">Rincian pencatatan penyerahan saldo kas periode {{ $settlement->periodLabel() }}.</p>
    </header>

    @if($settlement->isVoided())
        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-100/80 p-5 text-sm text-slate-800 dark:border-slate-800 dark:bg-slate-850 dark:text-slate-200">
            <div class="flex items-start gap-3">
                <span class="icon-badge-slate mt-0.5"><x-icon name="alert" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Catatan Setoran Ini Telah Dibatalkan (Void)</h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                        Dibatalkan pada {{ $preferences->formatDate($settlement->voided_at) }}. Catatan ini dipertahankan sebagai audit trail, namun tidak lagi mengurangi kewajiban kas belum disetor.
                    </p>
                    @if($settlement->void_reason)
                        <p class="mt-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Alasan: <span class="font-normal">{{ $settlement->void_reason }}</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($historicalDiscrepancy)
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
            <div class="flex items-start gap-3">
                <span class="icon-badge-rose mt-0.5"><x-icon name="alert" size="5"/></span>
                <div>
                    <h2 class="font-bold">Peringatan: Data Keuangan Periode Ini Telah Berubah</h2>
                    <p class="mt-1 text-xs sm:text-sm text-rose-800 dark:text-rose-300">
                        Transaksi pada periode ini telah ditambahkan, diedit, atau dihapus setelah setoran dicatat. Saldo awal saat setoran dibuat tidak cocok lagi dengan rekalkulasi data historis saat ini.
                    </p>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-xs rounded-xl bg-white/70 p-3 dark:bg-slate-900/60">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">Saldo saat disetor (Snapshot):</span>
                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ $currency }} {{ $preferences->formatMoney($historicalDiscrepancy['snapshot']) }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500 dark:text-slate-400">Saldo terkini (Rekalkulasi):</span>
                            <p class="font-bold text-rose-700 dark:text-rose-400">{{ $currency }} {{ $preferences->formatMoney($historicalDiscrepancy['recalculated']) }}</p>
                        </div>
                        <div class="col-span-2 border-t border-slate-200/60 pt-2 dark:border-slate-800">
                            <span class="text-slate-500 dark:text-slate-400">Selisih perubahan data:</span>
                            <p class="font-bold text-amber-700 dark:text-amber-400">{{ $currency }} {{ $preferences->formatMoney($historicalDiscrepancy['difference']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section class="section-card accent-teal mt-6 space-y-5">
        <div class="section-heading">
            <span class="icon-badge-teal"><x-icon name="settlement" size="5"/></span>
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Rincian Setoran</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Informasi pertanggungjawaban kas yang tercatat</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Periode Kas</span>
                <p class="mt-1 text-base font-bold text-slate-900 dark:text-white">{{ $settlement->periodLabel() }}</p>
                <p class="text-xs text-slate-400">Tanggal: {{ $settlement->period->format('d/m/Y') }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Tanggal Disetor</span>
                <p class="mt-1 text-base font-bold text-slate-900 dark:text-white">{{ $preferences->formatDate($settlement->settled_at) }}</p>
                <p class="text-xs text-slate-400">Dicatat: {{ $preferences->formatDate($settlement->created_at) }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Saldo Pertanggungjawaban (Snapshot)</span>
                <p class="mt-1 text-lg font-bold tabular-nums text-slate-900 dark:text-white">{{ $currency }} {{ $preferences->formatMoney($settlement->period_balance_snapshot) }}</p>
                <p class="text-xs text-slate-400">Nilai kas pada saat setoran dicatat</p>
            </div>

            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-4 dark:border-emerald-950/60 dark:bg-emerald-950/20">
                <span class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Jumlah Uang Disetorkan</span>
                <p class="mt-1 text-lg font-bold tabular-nums {{ $settlement->isVoided() ? 'line-through text-slate-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $currency }} {{ $preferences->formatMoney($settlement->settled_amount) }}</p>
                <p class="text-xs text-emerald-700/70 dark:text-emerald-400/70">Nominal fisik kas yang diserahkan</p>
            </div>
        </div>

        @php($discrepancy = $settlement->discrepancy())
        @if(! $discrepancy->isZero() && ! $settlement->isVoided())
            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs sm:text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                <strong>Selisih Setoran:</strong> {{ $currency }} {{ $preferences->formatMoney($discrepancy) }}.
                Jumlah yang disetorkan berbeda dari saldo pertanggungjawaban periode.
            </div>
        @endif

        @if($settlement->notes)
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Catatan</span>
                <p class="mt-1 text-sm text-slate-800 dark:text-slate-200 leading-relaxed">{{ $settlement->notes }}</p>
            </div>
        @endif

        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
            <span class="text-xs text-slate-400">ID Setoran: #{{ $settlement->id }}</span>
            @if($settlement->isActive())
                <form method="POST" action="{{ route('settlements.destroy', $settlement) }}"
                      onsubmit="return confirm('Batalkan catatan setoran periode {{ $settlement->periodLabel() }}? Setoran akan di-void dan periode ini kembali menjadi kas belum disetor.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger !min-h-10 text-xs">
                        <x-icon name="trash" size="4"/>
                        <span>Batalkan Catatan Setoran (Void)</span>
                    </button>
                </form>
            @else
                <span class="text-xs font-medium text-slate-400">Catatan telah dibatalkan (arsip audit)</span>
            @endif
        </div>
    </section>
</div>
@endsection
