@extends('layouts.app')
@section('title', 'Laporan Keuangan')
@section('content')
<div class="mx-auto max-w-7xl" x-data="{ filtersOpen: window.innerWidth >= 768 }" @resize.window="if (window.innerWidth >= 768) filtersOpen = true" @keydown.escape.window="if (window.innerWidth < 768 && filtersOpen) { filtersOpen = false; $nextTick(() => $refs.filterTrigger?.focus()) }">
    <header>
        <p class="section-kicker text-teal-700 dark:text-teal-400">Analisis ledger</p>
        <h1 class="page-title">Laporan keuangan</h1>
        <p class="page-description">Ringkasan transaksi dan arus kas keuangan tercatat untuk {{ $periodLabel }}.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button x-ref="filterTrigger" type="button" @click="filtersOpen = true; $nextTick(() => $refs.filterClose?.focus())" class="btn-secondary md:hidden" :aria-expanded="filtersOpen" aria-controls="report-filter-sheet">
                <x-icon name="settings" size="4"/>
                <span>Filter laporan</span>
            </button>
            <a href="{{ route('reports.export', $filters) }}" class="btn-secondary">
                <x-icon name="report" size="4"/>
                <span>Ekspor laporan CSV</span>
            </a>
        </div>
    </header>

    <div x-cloak x-show="filtersOpen" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/60 backdrop-blur-xs md:hidden" @click="filtersOpen = false; $nextTick(() => $refs.filterTrigger?.focus())" aria-hidden="true"></div>
    <form id="report-filter-sheet" x-cloak x-show="filtersOpen" @click.outside="if (window.innerWidth < 768) { filtersOpen = false; $nextTick(() => $refs.filterTrigger?.focus()) }" method="GET" action="{{ route('reports.index') }}" class="filter-surface report-filter-sheet fixed inset-x-0 bottom-0 z-[70] grid max-h-[85dvh] gap-3.5 overflow-y-auto rounded-t-3xl p-5 pb-24 shadow-2xl md:static md:z-auto md:mt-6 md:max-h-none md:grid-cols-2 md:rounded-2xl md:pb-5 xl:grid-cols-5" x-data="{ period: '{{ $filters['period'] }}' }" aria-label="Filter laporan">
        <div class="flex items-center justify-between md:hidden">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Filter laporan</h2>
            <button x-ref="filterClose" type="button" @click="filtersOpen = false; $nextTick(() => $refs.filterTrigger?.focus())" class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Tutup filter"><x-icon name="close"/></button>
        </div>
        <div>
            <label for="period" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Periode</label>
            <select id="period" name="period" x-model="period" class="form-control text-sm">
                <option value="daily">Hari ini</option>
                <option value="weekly">Minggu ini</option>
                <option value="monthly">Bulan ini</option>
                <option value="yearly">Tahun ini</option>
                <option value="custom">Rentang khusus</option>
            </select>
            <x-form-error name="period" />
        </div>
        <div x-show="period === 'custom'">
            <label for="date_from" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Tanggal mulai</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" :required="period === 'custom'" class="form-control text-sm">
            <x-form-error name="date_from" />
        </div>
        <div x-show="period === 'custom'">
            <label for="date_to" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Tanggal selesai</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" :required="period === 'custom'" class="form-control text-sm">
            <x-form-error name="date_to" />
        </div>
        <div>
            <label for="account_id" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Akun</label>
            <select id="account_id" name="account_id" class="form-control text-sm">
                <option value="">Semua akun</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string)($filters['account_id'] ?? '') === (string)$account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
            <x-form-error name="account_id" />
        </div>
        <div>
            <label for="category_id" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Kategori</label>
            <select id="category_id" name="category_id" class="form-control text-sm">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$category->id)>{{ App\Models\Category::TYPES[$category->type] }} — {{ $category->name }}</option>
                @endforeach
            </select>
            <x-form-error name="category_id" />
        </div>
        <div>
            <label for="type" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Jenis transaksi</label>
            <select id="type" name="type" class="form-control text-sm">
                <option value="">Semua jenis</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-form-error name="type" />
        </div>
        <div class="flex items-end gap-2">
            <button class="btn-primary flex-1">Tampilkan</button>
            <a href="{{ route('reports.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan laporan">
        <article class="premium-surface accent-emerald flex min-h-[148px] flex-col justify-between p-5">
            <span class="icon-badge-emerald"><x-icon name="transaction" size="5"/></span>
            <div class="mt-3">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total pemasukan</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $preferences->currency }} {{ number_format((float)$incomeTotal, 2, '.', ',') }}</p>
            </div>
        </article>
        <article class="premium-surface accent-rose flex min-h-[148px] flex-col justify-between p-5">
            <span class="icon-badge-rose"><x-icon name="bill" size="5"/></span>
            <div class="mt-3">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total pengeluaran</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-rose-700 dark:text-rose-300">{{ $preferences->currency }} {{ number_format((float)$expenseTotal, 2, '.', ',') }}</p>
            </div>
        </article>
        <article class="premium-surface accent-amber flex min-h-[148px] flex-col justify-between p-5">
            <span class="icon-badge-amber"><x-icon name="transfer" size="5"/></span>
            <div class="mt-3">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Biaya transfer</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ $preferences->currency }} {{ number_format((float)$transferFees, 2, '.', ',') }}</p>
            </div>
        </article>
        <article class="premium-surface accent-violet flex min-h-[148px] flex-col justify-between p-5">
            <span class="icon-badge-violet"><x-icon name="report" size="5"/></span>
            <div class="mt-3">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Arus kas bersih</p>
                <p class="mt-1 text-2xl font-bold tabular-nums {{ (float)$netCashFlow >= 0 ? 'text-violet-700 dark:text-violet-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $preferences->currency }} {{ number_format((float)$netCashFlow, 2, '.', ',') }}</p>
            </div>
        </article>
    </section>

    @if((float)$adjustmentIncrease > 0 || (float)$adjustmentDecrease > 0)
        <p class="mt-3 text-right text-xs text-slate-500 dark:text-slate-400">Penyesuaian ledger: +{{ $preferences->currency }} {{ number_format((float)$adjustmentIncrease, 2, '.', ',') }} / −{{ $preferences->currency }} {{ number_format((float)$adjustmentDecrease, 2, '.', ',') }}. Penyesuaian tidak dimasukkan ke arus kas.</p>
    @endif

    <section class="section-card accent-cyan mt-8">
        <div class="section-heading">
            <span class="icon-badge-cyan"><x-icon name="report" size="5"/></span>
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Tren harian</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Pemasukan dan pengeluaran berdasarkan tanggal transaksi.</p>
            </div>
        </div>
        @if(count($dailyChart['labels']))
            <div class="chart-frame mt-5 h-80">
                <canvas id="report-chart" aria-label="Grafik laporan pemasukan dan pengeluaran"></canvas>
            </div>
        @else
            <x-empty-state class="mt-5" icon="report" title="Tidak ada data grafik untuk filter ini." accent="cyan" />
        @endif
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-2">
        <article class="premium-surface accent-rose overflow-hidden">
            <div class="p-5">
                <div class="section-heading">
                    <span class="icon-badge-rose"><x-icon name="category" size="5"/></span>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Rincian kategori</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Transfer dan penyesuaian tanpa kategori tidak dihitung.</p>
                    </div>
                </div>
            </div>
            @if(empty($categoryBreakdown))
                <div class="border-t border-slate-100 p-6 text-sm text-slate-500 dark:border-slate-800">Tidak ada rincian kategori.</div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($categoryBreakdown as $row)
                        <div class="list-row flex items-center justify-between gap-4 px-5 py-3.5">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">{{ $row->name }}</p>
                                <span class="{{ $row->type === 'income' ? 'status-chip-emerald' : 'status-chip-rose' }} mt-1">
                                    {{ App\Models\Transaction::TYPES[$row->type] }}
                                </span>
                            </div>
                            <p class="font-bold tabular-nums text-slate-900 dark:text-white">{{ $preferences->currency }} {{ number_format((float)$row->total, 2, '.', ',') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="premium-surface accent-cyan overflow-hidden">
            <div class="p-5">
                <div class="section-heading">
                    <span class="icon-badge-cyan"><x-icon name="wallet" size="5"/></span>
                    <div>
                        <h2 class="font-bold text-slate-900 dark:text-white">Rincian akun</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Agregasi transaksi ledger per akun keuangan.</p>
                    </div>
                </div>
            </div>
            @if(empty($accountBreakdown))
                <div class="border-t border-slate-100 p-6 text-sm text-slate-500 dark:border-slate-800">Tidak ada rincian akun.</div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($accountBreakdown as $row)
                        <div class="list-row px-5 py-3.5">
                            <p class="font-bold text-slate-900 dark:text-white">{{ $row->name }}</p>
                            <div class="mt-2 grid grid-cols-2 gap-3 text-xs text-slate-500 dark:text-slate-400 sm:grid-cols-4">
                                <div class="rounded-lg border border-slate-100 bg-white/70 p-2 dark:border-slate-800 dark:bg-slate-950/40">
                                    <span class="text-slate-400">Masuk</span><br>
                                    <b class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format((float)$row->income_total, 2, '.', ',') }}</b>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-white/70 p-2 dark:border-slate-800 dark:bg-slate-950/40">
                                    <span class="text-slate-400">Keluar</span><br>
                                    <b class="font-bold text-rose-600 dark:text-rose-400">{{ number_format((float)$row->expense_total, 2, '.', ',') }}</b>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-white/70 p-2 dark:border-slate-800 dark:bg-slate-950/40">
                                    <span class="text-slate-400">Penyesuaian +</span><br>
                                    <b class="font-bold text-slate-800 dark:text-slate-200">{{ number_format((float)$row->adjustment_increase, 2, '.', ',') }}</b>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-white/70 p-2 dark:border-slate-800 dark:bg-slate-950/40">
                                    <span class="text-slate-400">Penyesuaian −</span><br>
                                    <b class="font-bold text-slate-800 dark:text-slate-200">{{ number_format((float)$row->adjustment_decrease, 2, '.', ',') }}</b>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="premium-surface mt-8 overflow-hidden">
        <div class="p-5">
            <div class="section-heading">
                <span class="icon-badge-violet"><x-icon name="transaction" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Rincian ledger</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Transaksi non-terhapus, diurutkan dari yang terbaru.</p>
                </div>
            </div>
        </div>
        @if($transactions->isEmpty())
            <div class="border-t border-slate-100 p-5 dark:border-slate-800">
                <x-empty-state icon="transaction" title="Tidak ada transaksi untuk filter ini." accent="violet" />
            </div>
        @else
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($transactions as $transaction)
                    <a href="{{ route('transactions.show', $transaction) }}" class="ledger-row sm:grid-cols-[7.5rem_1fr_1fr_auto] sm:items-center">
                        <p class="text-xs font-medium text-slate-400">{{ $preferences->formatDate($transaction->transaction_date) }}</p>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800 dark:text-slate-200">{{ $transaction->description ?: App\Models\Transaction::TYPES[$transaction->type] }}</p>
                            <x-category-badge :category="$transaction->category" class="mt-1 text-xs" />
                        </div>
                        <p class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $transaction->account->name }}</p>
                        <p class="font-bold tabular-nums sm:text-right {{ $transaction->type === 'income' || $transaction->adjustment_direction === 'increase' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $transaction->type === 'income' || $transaction->adjustment_direction === 'increase' ? '+' : '−' }} {{ $preferences->currency }} {{ number_format((float)$transaction->amount, 2, '.', ',') }}
                        </p>
                    </a>
                @endforeach
            </div>
            <div class="border-t border-slate-100 p-5 dark:border-slate-800">{{ $transactions->links() }}</div>
        @endif
    </section>

    <aside class="mt-8 rounded-2xl border border-amber-200/80 bg-amber-50/60 p-4 text-xs leading-relaxed text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200 sm:text-sm">
        <strong class="font-bold">Dasar perhitungan:</strong> arus kas bersih = pemasukan − pengeluaran − biaya transfer. Nominal transfer antar akun tidak dianggap pemasukan atau pengeluaran. Penyesuaian ledger, pembayaran utang/piutang, dan pergerakan target tabungan tidak diklasifikasikan sebagai pemasukan atau pengeluaran dalam laporan ini.
    </aside>
</div>

@if(count($dailyChart['labels']))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const report = {{ Illuminate\Support\Js::from($dailyChart) }};
    new window.Chart(document.getElementById('report-chart'), {
        type: 'bar',
        data: {
            labels: report.labels,
            datasets: [
                { label: 'Pemasukan', data: window.KasMeCharts.numericSeries(report.income), backgroundColor: '#10b981', hoverBackgroundColor: '#059669', borderRadius: 6, borderSkipped: false, categoryPercentage: .72, barPercentage: .8 },
                { label: 'Pengeluaran', data: window.KasMeCharts.numericSeries(report.expense), backgroundColor: '#f43f5e', hoverBackgroundColor: '#e11d48', borderRadius: 6, borderSkipped: false, categoryPercentage: .72, barPercentage: .8 },
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, resizeDelay: 50, scales: window.KasMeCharts.cartesianScales() },
    });
});
</script>
@endif
@endsection
