@extends('layouts.app')
@section('title', 'Setoran Kas')
@section('content')
<div class="mx-auto max-w-7xl" x-data="{
    settleModalOpen: false,
    selectedPeriod: '{{ $summary['previousPeriod'] }}',
    selectedPeriodLabel: '{{ $summary['previousPeriodLabel'] }}',
    periodBalance: '{{ $summary['previousPeriodCash'] }}',
    settledAmount: '{{ $summary['previousPeriodCash'] }}',
    settledAt: '{{ now()->toDateString() }}',
    notes: '',
}">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="section-kicker">Pertanggungjawaban kas</p>
            <h1 class="page-title">Setoran Kas Bulanan</h1>
            <p class="page-description">Pencatatan saldo kas periode bulanan yang disetorkan kepada atasan.</p>
        </div>
        @if(! $summary['previousPeriodSettled'])
            <button type="button" @click="settleModalOpen = true" class="btn-primary w-full sm:w-auto">
                <x-icon name="plus" size="4"/>
                <span>Catat Setoran {{ $summary['previousPeriodLabel'] }}</span>
            </button>
        @endif
    </header>

    @if(! $summary['isBaselineSet'])
        <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50/70 p-4 text-xs sm:text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-200 flex items-center justify-between gap-3">
            <div>
                <strong>Pertanggungjawaban kas belum diatur:</strong>
                Transaksi sebelum periode baseline tidak diakumulasi sebagai kas belum disetor.
            </div>
            <a href="{{ route('dashboard') }}" class="font-bold text-blue-700 underline dark:text-blue-300 shrink-0">Atur di Dasbor &rarr;</a>
        </div>
    @elseif($summary['baselineDate'])
        <div class="mt-4 text-xs text-slate-500 dark:text-slate-400">
            Pelacakan kas aktif mulai periode: <strong class="text-slate-700 dark:text-slate-300">{{ \Carbon\CarbonImmutable::parse($summary['baselineDate'])->locale('id')->translatedFormat('F Y') }}</strong>
        </div>
    @endif

    <!-- Status Bulan Lalu -->
    <section class="section-card accent-teal mt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3.5">
                <span class="icon-badge-teal mt-0.5"><x-icon name="settlement" size="5"/></span>
                <div>
                    <h2 class="font-bold text-slate-900 dark:text-white">Status Saldo Kas {{ $summary['previousPeriodLabel'] }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
                        Periode {{ $summary['previousPeriodStart'] }} s/d {{ $summary['previousPeriodEnd'] }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if($summary['previousPeriodSettled'])
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <x-icon name="check" size="3.5"/>
                                <span>Sudah Disetor</span>
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Tanggal: {{ $preferences->formatDate($summary['previousPeriodSettlement']->settled_at) }}
                                &middot; Jumlah: {{ $currency }} {{ $preferences->formatMoney($summary['previousPeriodSettlement']->settled_amount) }}
                            </span>
                        @elseif($summary['isPreviousBeforeBaseline'])
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <span>Periode sebelum pelacakan setoran</span>
                            </span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                Saldo kas: {{ $currency }} {{ $preferences->formatMoney($summary['previousPeriodCash']) }} (tidak diakumulasi sebagai kas belum disetor)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
                                <x-icon name="clock" size="3.5"/>
                                <span>Belum Disetor</span>
                            </span>
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                Saldo yang harus dipertanggungjawabkan: <strong>{{ $currency }} {{ $preferences->formatMoney($summary['previousPeriodCash']) }}</strong>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @if(! $summary['previousPeriodSettled'])
                <button type="button" @click="settleModalOpen = true" class="btn-secondary w-full shrink-0 sm:w-auto">
                    <span>Setorkan Sekarang</span>
                </button>
            @endif
        </div>
    </section>

    <!-- Riwayat Setoran Table -->
    <section class="section-card accent-slate mt-8">
        <div class="section-heading">
            <span class="icon-badge-slate"><x-icon name="calendar" size="5"/></span>
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Riwayat Setoran</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Daftar rekonsiliasi dan bukti setoran yang telah dicatat</p>
            </div>
        </div>

        @if($settlements->isEmpty())
            <x-empty-state class="mt-6" icon="settlement" title="Belum ada riwayat setoran yang dicatat." accent="teal" />
        @else
            <!-- Desktop Table -->
            <div class="mt-6 hidden overflow-x-auto lg:block">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:text-slate-400">
                            <th class="pb-3 pl-2">Periode</th>
                            <th class="pb-3 text-right">Saldo Periode (Snapshot)</th>
                            <th class="pb-3 text-right">Jumlah Disetor</th>
                            <th class="pb-3 text-right">Selisih</th>
                            <th class="pb-3 text-center">Tanggal Setor</th>
                            <th class="pb-3">Catatan</th>
                            <th class="pb-3 text-center">Audit</th>
                            <th class="pb-3 pr-2 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($settlements as $settlement)
                            @php($discrepancy = $settlement->discrepancy())
                            <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="py-3.5 pl-2 font-semibold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $settlement->periodLabel() }}</span>
                                        @if($settlement->isVoided())
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                                Dibatalkan
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 text-right font-medium tabular-nums text-slate-700 dark:text-slate-300">
                                    {{ $currency }} {{ $preferences->formatMoney($settlement->period_balance_snapshot) }}
                                </td>
                                <td class="py-3.5 text-right font-bold tabular-nums {{ $settlement->isVoided() ? 'line-through text-slate-400 dark:text-slate-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $currency }} {{ $preferences->formatMoney($settlement->settled_amount) }}
                                </td>
                                <td class="py-3.5 text-right font-medium tabular-nums {{ ! $discrepancy->isZero() && ! $settlement->isVoided() ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                    {{ $currency }} {{ $preferences->formatMoney($discrepancy) }}
                                </td>
                                <td class="py-3.5 text-center text-xs text-slate-500 dark:text-slate-400">
                                    {{ $preferences->formatDate($settlement->settled_at) }}
                                </td>
                                <td class="max-w-48 truncate py-3.5 text-xs text-slate-500 dark:text-slate-400" title="{{ $settlement->notes }}">
                                    {{ $settlement->notes ?: '-' }}
                                </td>
                                <td class="py-3.5 text-center">
                                    @if($settlement->isVoided())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400" title="Alasan: {{ $settlement->void_reason }}">
                                            <span>Void</span>
                                        </span>
                                    @elseif($settlement->historicalDiscrepancy)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400" title="Data transaksi periode ini berubah setelah setoran dicatat">
                                            <x-icon name="alert" size="3"/>
                                            <span>Data Berubah</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                            <x-icon name="check" size="3.5"/>
                                            <span>Sesuai</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('settlements.show', $settlement) }}" class="btn-secondary !min-h-8 !px-2.5 !py-1 text-xs">
                                            Detail
                                        </a>
                                        @if($settlement->isActive())
                                            <form method="POST" action="{{ route('settlements.destroy', $settlement) }}"
                                                  onsubmit="return confirm('Batalkan catatan setoran periode {{ $settlement->periodLabel() }}? Setoran akan di-void dan periode ini kembali menjadi kas belum disetor.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-danger !min-h-8 !px-2.5 !py-1 text-xs" title="Batalkan setoran (Void)">
                                                    <x-icon name="trash" size="3.5"/>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="mt-4 divide-y divide-slate-100 lg:hidden dark:divide-slate-800/80">
                @foreach($settlements as $settlement)
                    @php($discrepancy = $settlement->discrepancy())
                    <article class="py-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white">{{ $settlement->periodLabel() }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Disetor: {{ $preferences->formatDate($settlement->settled_at) }}</p>
                            </div>
                            @if($settlement->isVoided())
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    <span>Dibatalkan</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <x-icon name="check" size="3"/>
                                    <span>Sudah Disetor</span>
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-50/80 p-3 text-xs dark:bg-slate-800/60">
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Saldo Periode:</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $currency }} {{ $preferences->formatMoney($settlement->period_balance_snapshot) }}</p>
                            </div>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Jumlah Disetor:</span>
                                <p class="font-bold {{ $settlement->isVoided() ? 'line-through text-slate-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $currency }} {{ $preferences->formatMoney($settlement->settled_amount) }}</p>
                            </div>
                            @if(! $discrepancy->isZero() && ! $settlement->isVoided())
                                <div class="col-span-2 text-amber-600 dark:text-amber-400">
                                    <span>Selisih: {{ $currency }} {{ $preferences->formatMoney($discrepancy) }}</span>
                                </div>
                            @endif
                        </div>

                        @if($settlement->isVoided() && $settlement->void_reason)
                            <div class="rounded-xl border border-slate-200 bg-slate-100/70 p-2.5 text-xs text-slate-700 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-300">
                                <strong>Alasan pembatalan:</strong> {{ $settlement->void_reason }}
                            </div>
                        @elseif($settlement->historicalDiscrepancy)
                            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-2.5 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
                                <strong>Peringatan:</strong> Data keuangan periode ini telah dimodifikasi setelah setoran dicatat. Saldo terkini adalah {{ $currency }} {{ $preferences->formatMoney($settlement->historicalDiscrepancy['recalculated']) }}.
                            </div>
                        @endif

                        @if($settlement->notes)
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">"{{ $settlement->notes }}"</p>
                        @endif

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <a href="{{ route('settlements.show', $settlement) }}" class="btn-secondary !min-h-9 text-xs flex-1 text-center justify-center">
                                Detail
                            </a>
                            @if($settlement->isActive())
                                <form method="POST" action="{{ route('settlements.destroy', $settlement) }}"
                                      onsubmit="return confirm('Batalkan catatan setoran periode {{ $settlement->periodLabel() }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger !min-h-9 !px-3 text-xs">
                                        <x-icon name="trash" size="4"/>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $settlements->links() }}
            </div>
        @endif
    </section>

    <!-- Modal Catat Setoran -->
    <div x-cloak x-show="settleModalOpen" class="fixed inset-0 z-50 grid place-items-end p-4 sm:place-items-center" role="dialog" aria-modal="true" aria-labelledby="settle-modal-title">
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
                    <h2 id="settle-modal-title" class="text-lg font-bold text-slate-900 dark:text-white">Catat Setoran Kas Bulanan</h2>
                </div>
                <button type="button" @click="settleModalOpen = false" class="rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400">
                    <x-icon name="close" size="5"/>
                </button>
            </div>

            <form method="POST" action="{{ route('settlements.store') }}" class="mt-5 space-y-4"
                  @submit="if(!confirm(`Catat setoran kas ${selectedPeriodLabel} sebesar {{ $currency }} ${settledAmount}?`)) { $event.preventDefault(); }">
                @csrf
                <input type="hidden" name="period" :value="selectedPeriod">

                <div>
                    <label class="form-label">Periode yang Disetorkan</label>
                    <div class="form-control bg-slate-50 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <span x-text="selectedPeriodLabel"></span>
                    </div>
                    <p class="form-helper">Hanya periode bulan yang sudah selesai yang dapat disetorkan.</p>
                </div>

                <div>
                    <label class="form-label">Saldo Periode Berdasarkan Catatan</label>
                    <div class="form-control bg-slate-50 font-bold tabular-nums text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                        {{ $currency }} {{ $preferences->formatMoney($summary['previousPeriodCash']) }}
                    </div>
                    <p class="form-helper">Saldo kas yang dihasilkan dari seluruh pemasukan, pengeluaran, penyesuaian, dan mutasi pada periode tersebut.</p>
                </div>

                <div>
                    <label for="settled_amount" class="form-label">Jumlah Uang yang Disetorkan ({{ $currency }})</label>
                    <input type="number" id="settled_amount" name="settled_amount" step="0.01" min="0" required
                           x-model="settledAmount" class="form-control text-lg font-bold tabular-nums">
                    <template x-if="settledAmount && settledAmount !== periodBalance">
                        <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400">
                            Perhatian: Jumlah yang diinput berbeda dari saldo pertanggungjawaban periode.
                        </p>
                    </template>
                </div>

                <div>
                    <label for="settled_at" class="form-label">Tanggal Penyerahan / Setoran</label>
                    <input type="date" id="settled_at" name="settled_at" required x-model="settledAt" class="form-control">
                </div>

                <div>
                    <label for="notes" class="form-label">Catatan (Opsional)</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="1000" x-model="notes"
                              class="form-control" placeholder="Contoh: Disetor tunai ke Pak Budi di kantor"></textarea>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="settleModalOpen = false" class="btn-secondary w-full sm:w-auto">Batal</button>
                    <button type="submit" class="btn-primary w-full sm:w-auto">Simpan Setoran</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
