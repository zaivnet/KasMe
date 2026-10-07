@extends('layouts.app')
@section('title', 'Dasbor')
@section('content')
<div class="mx-auto max-w-7xl" x-data="{
    settleModalOpen: false,
    settledAmount: '{{ $previousPeriodCash }}',
    settledAt: '{{ now()->toDateString() }}',
    notes: '',
    baselineModalOpen: false,
    baselinePeriod: '{{ $currentPeriod }}',
}">
    <header>
        <p class="section-kicker">Ringkasan keuangan</p>
        <h1 class="page-title">Dasbor</h1>
        <p class="page-description">Aktivitas keuangan tercatat untuk {{ $periodLabel }}.</p>
    </header>

    @if(! $isBaselineSet)
        <!-- ONBOARDING BANNER: AKTIFKAN PERTANGGUNGJAWABAN KAS -->
        <section class="mt-6 rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50/60 p-4.5 sm:p-5 shadow-xs dark:border-blue-900/60 dark:bg-gradient-to-r dark:from-blue-950/40 dark:to-indigo-950/30">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3.5">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-600 text-white shadow-sm dark:bg-blue-500">
                        <x-icon name="wallet" size="5"/>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">Aktifkan Pertanggungjawaban Kas</h2>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 sm:text-sm leading-relaxed max-w-2xl">
                            Pilih periode mulai pelacakan setoran. Transaksi sebelum periode tersebut tetap tersimpan dan tetap muncul dalam laporan, tetapi tidak akan dianggap sebagai kas yang belum disetor kepada atasan.
                        </p>
                    </div>
                </div>
                <button type="button" @click="baselineModalOpen = true" class="btn-primary shrink-0 w-full sm:w-auto">
                    <span>Atur Periode Mulai</span>
                </button>
            </div>
        </section>
    @endif

    <section class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Ringkasan pertanggungjawaban kas">
        <!-- 1. KAS BELUM DISETOR -->
        <article class="premium-surface accent-blue flex min-h-[148px] flex-col justify-between overflow-hidden p-4 sm:min-h-[160px] sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="icon-badge-blue"><x-icon name="wallet" size="5"/></div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 sm:text-xs">Tanggung jawab kas</span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400 sm:text-sm">Kas belum disetor</p>
                <p class="mt-1.5 break-words text-lg font-bold tracking-tight tabular-nums sm:text-2xl text-blue-900 dark:text-blue-200">{{ $currency }} {{ $preferences->formatMoney($outstandingCash) }}</p>
                @if($hasCarryOver)
                    <div class="mt-1.5 space-y-0.5 text-[11px] sm:text-xs">
                        <p class="text-slate-500 dark:text-slate-400">Kas {{ $currentPeriodLabel }}: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $currency }} {{ $preferences->formatMoney($currentPeriodCash) }}</span></p>
                        <p class="font-medium text-amber-600 dark:text-amber-400">Sisa periode sebelumnya: <span class="font-semibold">{{ $currency }} {{ $preferences->formatMoney($historicalCarryOver) }}</span></p>
                    </div>
                @else
                    <p class="mt-1.5 text-xs font-medium text-slate-400 dark:text-slate-500">Kas berjalan {{ $currentPeriodLabel }}</p>
                @endif
            </div>
        </article>

        <!-- 2. PEMASUKAN BULAN INI -->
        <article class="premium-surface accent-emerald flex min-h-[148px] flex-col justify-between overflow-hidden p-4 sm:min-h-[160px] sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="icon-badge-emerald"><x-icon name="transaction" size="5"/></div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 sm:text-xs">Bulan ini</span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400 sm:text-sm">Pemasukan bulan ini</p>
                <p class="mt-1.5 break-words text-lg font-bold tracking-tight tabular-nums sm:text-2xl text-emerald-700 dark:text-emerald-300">{{ $currency }} {{ number_format((float)$income, 2, '.', ',') }}</p>
                <p class="mt-1.5 hidden text-xs font-medium text-slate-400 dark:text-slate-500 sm:block">Dana masuk tercatat</p>
            </div>
        </article>

        <!-- 3. PENGELUARAN BULAN INI -->
        <article class="premium-surface accent-rose flex min-h-[148px] flex-col justify-between overflow-hidden p-4 sm:min-h-[160px] sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="icon-badge-rose"><x-icon name="bill" size="5"/></div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 sm:text-xs">Bulan ini</span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400 sm:text-sm">Pengeluaran bulan ini</p>
                <p class="mt-1.5 break-words text-lg font-bold tracking-tight tabular-nums sm:text-2xl text-rose-700 dark:text-rose-300">{{ $currency }} {{ number_format((float)$expense, 2, '.', ',') }}</p>
                <p class="mt-1.5 hidden text-xs font-medium text-slate-400 dark:text-slate-500 sm:block">Dana keluar tercatat</p>
            </div>
        </article>

        <!-- 4. SALDO KAS BULAN LALU -->
        <article class="premium-surface accent-violet flex min-h-[148px] flex-col justify-between overflow-hidden p-4 sm:min-h-[160px] sm:p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="icon-badge-violet"><x-icon name="report" size="5"/></div>
                @if($previousPeriodSettled)
                    @if($previousPeriodSettlement->hasDiscrepancy())
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 sm:text-xs">
                            <span>Disetor sebagian</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 sm:text-xs">
                            <x-icon name="check" size="3"/>
                            <span>Sudah disetor</span>
                        </span>
                    @endif
                @elseif($isPreviousBeforeBaseline)
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300 sm:text-xs" title="Periode sebelum pelacakan setoran">
                        <span>Sebelum pelacakan</span>
                    </span>
                @elseif($hasPreviousPeriodData)
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 sm:text-xs">
                        <span>Belum disetor</span>
                    </span>
                @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 sm:text-xs">Bulan lalu</span>
                @endif
            </div>
            <div class="mt-3">
                <p class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400 sm:text-sm">Saldo kas {{ $previousPeriodLabel }}</p>
                <p class="mt-1.5 break-words text-lg font-bold tracking-tight tabular-nums sm:text-2xl text-violet-700 dark:text-violet-300">{{ $currency }} {{ $preferences->formatMoney($previousPeriodCash) }}</p>
                @if($previousPeriodSettled)
                    @if($previousPeriodSettlement->hasDiscrepancy())
                        <div class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            <p>Disetor: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $currency }} {{ $preferences->formatMoney($previousPeriodSettlement->settled_amount) }}</span></p>
                            <p class="text-amber-600 dark:text-amber-400">Sisa: <span class="font-semibold">{{ $currency }} {{ $preferences->formatMoney($previousPeriodSettlement->discrepancy()) }}</span></p>
                        </div>
                    @else
                        <div class="mt-1.5 flex flex-wrap items-center justify-between gap-1 text-xs text-slate-500 dark:text-slate-400">
                            <span>Disetor: {{ $preferences->formatDate($previousPeriodSettlement->settled_at) }}</span>
                            <a href="{{ route('settlements.index') }}" class="font-semibold text-teal-700 hover:underline dark:text-teal-400">Riwayat &rarr;</a>
                        </div>
                    @endif
                @elseif($isPreviousBeforeBaseline)
                    <p class="mt-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">Periode sebelum pelacakan setoran</p>
                @elseif($hasPreviousPeriodData)
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-1.5">
                        <button type="button" @click="settleModalOpen = true" class="btn-primary !min-h-8 !px-2.5 !py-1 text-xs">
                            Catat Setoran
                        </button>
                        <a href="{{ route('settlements.index') }}" class="text-xs font-semibold text-teal-700 hover:underline dark:text-teal-400">
                            Riwayat &rarr;
                        </a>
                    </div>
                @else
                    <p class="mt-1.5 hidden text-xs font-medium text-slate-400 dark:text-slate-500 sm:block">Belum ada data periode sebelumnya</p>
                @endif
            </div>
        </article>
    </section>

    @if((float)$fees > 0)
        <p class="mt-3 text-right text-xs text-slate-500 dark:text-slate-400">Termasuk {{ $currency }} {{ number_format((float)$fees, 2, '.', ',') }} biaya transfer bulan ini.</p>
    @endif

    <section class="mt-8 grid gap-6 xl:grid-cols-2">
        <article class="section-card accent-emerald">
            <div class="section-heading">
                <span class="icon-badge-emerald"><x-icon name="report" size="5"/></span>
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-900 dark:text-white">Pemasukan vs pengeluaran</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Total harian untuk {{ $periodLabel }}</p>
                </div>
            </div>
            @if(count($cashFlowChart['labels']))
                <div class="chart-frame mt-5 h-72">
                    <canvas id="cash-flow-chart" aria-label="Grafik pemasukan versus pengeluaran"></canvas>
                </div>
            @else
                <x-empty-state class="mt-5 min-h-72 grid content-center" icon="report" title="Belum ada data pemasukan atau pengeluaran bulan ini." accent="emerald" />
            @endif
        </article>

        <article class="section-card accent-rose">
            <div class="section-heading">
                <span class="icon-badge-rose"><x-icon name="category" size="5"/></span>
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-900 dark:text-white">Pengeluaran per kategori</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Pengeluaran tercatat untuk {{ $periodLabel }}</p>
                </div>
            </div>
            @if(count($categoryChart['labels']))
                <div class="chart-frame mt-5 h-72">
                    <canvas id="category-chart" aria-label="Grafik pengeluaran berdasarkan kategori"></canvas>
                </div>
            @else
                <x-empty-state class="mt-5 min-h-72 grid content-center" icon="category" title="Belum ada data kategori pengeluaran bulan ini." accent="rose" />
            @endif
        </article>
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-[1fr_1.4fr]">
        <article class="section-card accent-cyan">
            <div class="flex items-center justify-between gap-4">
                <div class="section-heading">
                    <span class="icon-badge-cyan"><x-icon name="wallet" size="5"/></span>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Ringkasan akun</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total kumulatif akuntansi: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $currency }} {{ number_format((float) $totalBalance, 2, '.', ',') }}</span></p>
                    </div>
                </div>
                <a href="{{ route('accounts.index') }}" class="shrink-0 text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">Lihat semua</a>
            </div>
            @if($accounts->isEmpty())
                <x-empty-state class="mt-5" icon="wallet" title="Belum ada akun." accent="cyan" />
            @else
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($accounts as $account)
                        <a href="{{ route('accounts.show', $account) }}" class="list-row flex min-w-0 items-center justify-between gap-3 rounded-xl px-2 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-white shadow-xs" style="background-color: {{ $account->color ?: '#059669' }}">
                                    <x-account-icon :icon="$account->icon" :type="$account->type" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $account->name }}</p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ App\Models\Account::TYPES[$account->type] }} · {{ $account->is_active ? 'Aktif' : 'Tidak aktif' }}</p>
                                </div>
                            </div>
                            <p class="shrink-0 text-right text-sm font-bold tabular-nums text-slate-800 dark:text-slate-100 sm:text-base">{{ $account->currency }} {{ number_format((float)$balances[$account->id], 2, '.', ',') }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="section-card accent-violet">
            <div class="flex items-center justify-between gap-4">
                <div class="section-heading">
                    <span class="icon-badge-violet"><x-icon name="transaction" size="5"/></span>
                    <h2 class="font-bold text-slate-900 dark:text-white">Transaksi terbaru</h2>
                </div>
                <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-violet-700 hover:underline dark:text-violet-400">Lihat semua</a>
            </div>
            @if($recentTransactions->isEmpty())
                <x-empty-state class="mt-5" icon="transaction" title="Belum ada transaksi." accent="violet" />
            @else
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($recentTransactions as $transaction)
                        <a href="{{ route('transactions.show', $transaction) }}" class="list-row grid min-w-0 gap-2 rounded-xl px-2 py-3 sm:grid-cols-[5.5rem_1fr_auto] sm:items-center">
                            <p class="text-xs font-medium text-slate-400">{{ $preferences->formatDate($transaction->transaction_date) }}</p>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800 dark:text-slate-200">{{ $transaction->description ?: App\Models\Transaction::TYPES[$transaction->type] }}</p>
                                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $transaction->account->name }}</span>
                                    <x-category-badge :category="$transaction->category" :fallback="$transaction->adjustment_direction ? App\Models\Transaction::ADJUSTMENT_DIRECTIONS[$transaction->adjustment_direction] : 'Tanpa kategori'" class="text-xs" />
                                </div>
                            </div>
                            <p class="font-bold tabular-nums {{ $transaction->type === 'income' || $transaction->adjustment_direction === 'increase' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $transaction->type === 'income' || $transaction->adjustment_direction === 'increase' ? '+' : '-' }} {{ number_format((float)$transaction->amount, 2, '.', ',') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="section-card accent-amber mt-8">
        <div class="flex items-center justify-between gap-4">
            <div class="section-heading">
                <span class="icon-badge-amber"><x-icon name="budget" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Anggaran bulanan</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Target pengeluaran untuk {{ $periodLabel }}</p>
                </div>
            </div>
            <a href="{{ route('budgets.index') }}" class="text-sm font-semibold text-amber-700 hover:underline dark:text-amber-400">Lihat semua</a>
        </div>
        @if($budgets->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-amber-200/90 bg-amber-50/30 p-6 text-center text-sm text-slate-500 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-slate-400">
                Belum ada anggaran untuk bulan ini. <a href="{{ route('budgets.create') }}" class="font-semibold text-emerald-700 underline dark:text-emerald-400">Tambah anggaran</a>.
            </div>
        @else
            <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total terpakai</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $currency }} {{ number_format((float) $budgetUsed, 2, '.', ',') }} <span class="text-sm font-normal text-slate-400">dari {{ number_format((float) $budgetAmount, 2, '.', ',') }}</span></p>
                </div>
                <p class="font-bold tabular-nums {{ $budgetPercentage > 100 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }}">{{ number_format($budgetPercentage, 1) }}%</p>
            </div>
            <div class="progress-track mt-3">
                <div class="progress-fill {{ $budgetPercentage > 100 ? 'bg-gradient-to-r from-rose-500 to-red-600' : ($budgetPercentage >= 80 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-emerald-500 to-teal-500') }}" style="width: {{ min(100, $budgetPercentage) }}%"></div>
            </div>
        @endif
    </section>

    <section class="section-card accent-amber mt-8">
        <div class="flex items-center justify-between gap-4">
            <div class="section-heading">
                <span class="icon-badge-amber"><x-icon name="bill" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Tagihan mendatang</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Terlambat dan jatuh tempo dalam 30 hari ke depan</p>
                </div>
            </div>
            <a href="{{ route('bills.index') }}" class="text-sm font-semibold text-amber-700 hover:underline dark:text-amber-400">Lihat semua</a>
        </div>
        @if($upcomingBills->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-amber-200/90 bg-amber-50/30 p-6 text-center text-sm text-slate-500 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-slate-400">
                Tidak ada tagihan mendatang atau terlambat.
            </div>
        @else
            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800/80">
                @foreach($upcomingBills as $bill)
                    @php($billStatus = $bill->effectiveStatus())
                    <a href="{{ route('bills.edit', $bill) }}" class="grid gap-1 py-3 sm:grid-cols-[7rem_1fr_auto] sm:items-center">
                        <p class="text-xs {{ $billStatus === 'overdue' ? 'font-bold text-rose-600 dark:text-rose-400' : 'font-medium text-slate-400' }}">{{ $preferences->formatDate($bill->due_date) }}</p>
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $bill->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $bill->category?->name ?? 'Tanpa kategori' }} · {{ App\Models\Bill::STATUSES[$billStatus] }}</p>
                        </div>
                        <p class="font-bold tabular-nums text-slate-900 dark:text-white">{{ $currency }} {{ number_format((float) $bill->amount, 2, '.', ',') }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Modal Catat Setoran Kas -->
    <div x-cloak x-show="settleModalOpen" class="fixed inset-0 z-50 grid place-items-end p-4 sm:place-items-center" role="dialog" aria-modal="true" aria-labelledby="dash-settle-modal-title">
        <div x-show="settleModalOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="settleModalOpen = false"></div>
        <section x-show="settleModalOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                 class="relative w-full max-w-lg rounded-3xl border border-slate-200/80 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="icon-badge-teal"><x-icon name="settlement" size="5"/></span>
                    <h2 id="dash-settle-modal-title" class="text-lg font-bold text-slate-900 dark:text-white">Catat Setoran Kas Bulanan</h2>
                </div>
                <button type="button" @click="settleModalOpen = false" class="rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400">
                    <x-icon name="close" size="5"/>
                </button>
            </div>

            <form method="POST" action="{{ route('settlements.store') }}" class="mt-5 space-y-4"
                  @submit="if(!confirm('Catat setoran kas {{ $previousPeriodLabel }} sebesar {{ $currency }} ' + settledAmount + '?')) { $event.preventDefault(); }">
                @csrf
                <input type="hidden" name="period" value="{{ $previousPeriod }}">

                <div>
                    <label class="form-label">Periode yang Disetorkan</label>
                    <div class="form-control bg-slate-50 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ $previousPeriodLabel }}
                    </div>
                    <p class="form-helper">Hanya periode bulan yang sudah selesai yang dapat disetorkan.</p>
                </div>

                <div>
                    <label class="form-label">Saldo Periode Berdasarkan Catatan</label>
                    <div class="form-control bg-slate-50 font-bold tabular-nums text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                        {{ $currency }} {{ $preferences->formatMoney($previousPeriodCash) }}
                    </div>
                    <p class="form-helper">Saldo kas yang dihasilkan dari seluruh pemasukan, pengeluaran, penyesuaian, dan mutasi pada periode tersebut.</p>
                </div>

                <div>
                    <label for="dash_settled_amount" class="form-label">Jumlah Uang yang Disetorkan ({{ $currency }})</label>
                    <input type="number" id="dash_settled_amount" name="settled_amount" step="0.01" min="0" required
                           x-model="settledAmount" class="form-control text-lg font-bold tabular-nums">
                    <template x-if="settledAmount && settledAmount !== '{{ $previousPeriodCash }}'">
                        <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400">
                            Perhatian: Jumlah yang diinput berbeda dari saldo pertanggungjawaban periode ({{ $currency }} {{ $preferences->formatMoney($previousPeriodCash) }}).
                        </p>
                    </template>
                </div>

                <div>
                    <label for="dash_settled_at" class="form-label">Tanggal Penyerahan / Setoran</label>
                    <input type="date" id="dash_settled_at" name="settled_at" required x-model="settledAt" class="form-control">
                </div>

                <div>
                    <label for="dash_notes" class="form-label">Catatan (Opsional)</label>
                    <textarea id="dash_notes" name="notes" rows="2" maxlength="1000" x-model="notes"
                              class="form-control" placeholder="Contoh: Disetor tunai ke atasan di kantor"></textarea>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="settleModalOpen = false" class="btn-secondary w-full sm:w-auto">Batal</button>
                    <button type="submit" class="btn-primary w-full sm:w-auto">Simpan Setoran</button>
                </div>
            </form>
        </section>
    </div>

    <!-- MODAL AKTIFKAN PERTANGGUNGJAWABAN KAS -->
    <div x-cloak x-show="baselineModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
         @keydown.escape.window="baselineModalOpen = false">
        <div class="fixed inset-0" @click="baselineModalOpen = false"></div>
        <section class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800"
                 @click.stop role="dialog" aria-modal="true" aria-labelledby="baseline-modal-title">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="icon-badge-blue"><x-icon name="wallet" size="5"/></span>
                    <div>
                        <h2 id="baseline-modal-title" class="text-base font-bold text-slate-900 dark:text-white sm:text-lg">Aktifkan Pertanggungjawaban Kas</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Tentukan periode awal pelacakan setoran</p>
                    </div>
                </div>
                <button type="button" @click="baselineModalOpen = false" class="rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400">
                    <x-icon name="close" size="5"/>
                </button>
            </div>

            <form method="POST" action="{{ route('settlements.baseline') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="dash_baseline_period" class="form-label">Mulai Periode</label>
                    <input type="month" id="dash_baseline_period" name="cash_accountability_start_period" required
                           value="{{ substr($currentPeriod, 0, 7) }}" max="{{ substr($currentPeriod, 0, 7) }}"
                           class="form-control text-base font-semibold">
                    <p class="form-helper">Format bulan dan tahun (misal: {{ substr($currentPeriod, 0, 7) }}).</p>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-3.5 text-xs text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-200 space-y-1.5 leading-relaxed">
                    <p class="font-semibold text-blue-950 dark:text-blue-100">Penjelasan Ketentuan:</p>
                    <p>Transaksi sebelum periode ini tetap tersimpan dan tetap muncul dalam laporan, tetapi tidak akan dianggap sebagai kas yang belum disetor.</p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="baselineModalOpen = false" class="btn-secondary w-full sm:w-auto">Batal</button>
                    <button type="submit" class="btn-primary w-full sm:w-auto">Aktifkan Pelacakan</button>
                </div>
            </form>
        </section>
    </div>
</div>

@if(count($cashFlowChart['labels']) || count($categoryChart['labels']))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cashFlow = {{ Illuminate\Support\Js::from($cashFlowChart) }};
    const categories = {{ Illuminate\Support\Js::from($categoryChart) }};
    const textColor = document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569';
    if (cashFlow.labels.length) new window.Chart(document.getElementById('cash-flow-chart'), {
        type: 'bar',
        data: {
            labels: cashFlow.labels,
            datasets: [
                { label: 'Pemasukan', data: window.KasMeCharts.numericSeries(cashFlow.income), backgroundColor: '#10b981', hoverBackgroundColor: '#059669', borderRadius: 6, borderSkipped: false, categoryPercentage: .72, barPercentage: .8 },
                { label: 'Pengeluaran', data: window.KasMeCharts.numericSeries(cashFlow.expense), backgroundColor: '#f43f5e', hoverBackgroundColor: '#e11d48', borderRadius: 6, borderSkipped: false, categoryPercentage: .72, barPercentage: .8 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, resizeDelay: 50, scales: window.KasMeCharts.cartesianScales() },
    });
    if (categories.labels.length) new window.Chart(document.getElementById('category-chart'), {
        type: 'doughnut',
        data: {
            labels: categories.labels,
            datasets: [{
                data: window.KasMeCharts.numericSeries(categories.values),
                backgroundColor: ['#0d9488', '#f43f5e', '#f59e0b', '#3b82f6', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'],
                borderWidth: 0,
                borderRadius: 4,
                spacing: 3,
                hoverOffset: 6,
                radius: '90%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 50,
            cutout: '72%',
            rotation: -90,
            circumference: 360,
            layout: { padding: 8 },
            plugins: { legend: { position: 'bottom', labels: { color: textColor, padding: 14 } } }
        },
    });
});
</script>
@endif
@endsection
