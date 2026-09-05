@extends('layouts.app')
@section('title', 'Dasbor')
@section('content')
<div class="mx-auto max-w-7xl">
    <header>
        <p class="section-kicker">Ringkasan keuangan</p>
        <h1 class="page-title">Dasbor</h1>
        <p class="page-description">Aktivitas keuangan tercatat untuk {{ $periodLabel }}.</p>
    </header>

    <section class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Ringkasan keuangan">
        @foreach([
            ['Total saldo', $totalBalance, 'wallet', 'accent-blue', 'icon-badge-blue', 'text-blue-900 dark:text-blue-200', 'Di seluruh akun aktif'],
            ['Pemasukan bulan ini', $income, 'transaction', 'accent-emerald', 'icon-badge-emerald', 'text-emerald-700 dark:text-emerald-300', 'Dana masuk tercatat'],
            ['Pengeluaran bulan ini', $expense, 'bill', 'accent-rose', 'icon-badge-rose', 'text-rose-700 dark:text-rose-300', 'Dana keluar tercatat'],
            ['Arus kas bersih', $netCashFlow, 'report', 'accent-violet', 'icon-badge-violet', (float)$netCashFlow >= 0 ? 'text-violet-700 dark:text-violet-300' : 'text-rose-700 dark:text-rose-300', 'Setelah pengeluaran & biaya']
        ] as [$label, $value, $icon, $surface, $badge, $class, $support])
            <article class="premium-surface {{ $surface }} flex min-h-[148px] flex-col justify-between overflow-hidden p-4 sm:min-h-[160px] sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <div class="{{ $badge }}"><x-icon :name="$icon" size="5"/></div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 sm:text-xs">Bulan ini</span>
                </div>
                <div class="mt-3">
                    <p class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400 sm:text-sm">{{ $label }}</p>
                    <p class="mt-1.5 break-words text-lg font-bold tracking-tight tabular-nums sm:text-2xl {{ $class }}">{{ $currency }} {{ number_format((float)$value, 2, '.', ',') }}</p>
                    <p class="mt-1.5 hidden text-xs font-medium text-slate-400 dark:text-slate-500 sm:block">{{ $support }}</p>
                </div>
            </article>
        @endforeach
    </section>

    @if((float)$fees > 0)
        <p class="mt-3 text-right text-xs text-slate-500 dark:text-slate-400">Arus kas bersih mencakup {{ $currency }} {{ number_format((float)$fees, 2, '.', ',') }} biaya transfer bulan ini.</p>
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
                    <h2 class="font-bold text-slate-900 dark:text-white">Ringkasan akun</h2>
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
