@extends('layouts.app')
@section('title', 'Pratinjau Restore')
@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-4">
        <a href="{{ route('backups.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke Backup & Restore</a>
    </div>

    <header>
        <p class="section-kicker text-rose-600 dark:text-rose-400">Tindakan Destruktif</p>
        <h1 class="page-title">Pratinjau Pemulihan Data</h1>
        <p class="page-description">Tinjau rincian arsip cadangan sebelum mengganti data keuangan aktif aplikasi.</p>
    </header>

    <!-- Critical Alert Box -->
    <div class="mt-6 rounded-3xl border border-rose-200 bg-rose-50/80 p-5 shadow-xs dark:border-rose-900/60 dark:bg-rose-950/40">
        <div class="flex items-start gap-3.5">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-600 text-white shadow-xs">
                <x-icon name="trash" size="5" />
            </span>
            <div>
                <h2 class="font-bold text-rose-950 dark:text-rose-200 text-base">Peringatan Penting Pemulihan Data</h2>
                <p class="mt-1 text-xs sm:text-sm leading-relaxed text-rose-800 dark:text-rose-300">
                    Data KasMe saat ini akan diganti dengan seluruh data dari berkas cadangan ini. Sebagai pengaman, sistem akan secara otomatis membuat cadangan darurat (<strong>pre-restore backup</strong>) sebelum proses restorasi dimulai.
                </p>
            </div>
        </div>
    </div>

    <!-- Diagnostic Details Card -->
    <div class="section-card accent-amber mt-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Informasi Arsip Cadangan</h2>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Nama Berkas</dt>
                <dd class="mt-0.5 font-bold text-slate-900 dark:text-white truncate" title="{{ $filename }}">{{ $filename }}</dd>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Ukuran Arsip</dt>
                <dd class="mt-0.5 font-bold text-slate-900 dark:text-white">{{ $preview['formatted_size'] }}</dd>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Waktu Pembuatan</dt>
                <dd class="mt-0.5 font-bold text-slate-900 dark:text-white">
                    {{ $preview['backup_date'] ? \Carbon\Carbon::parse($preview['backup_date'])->locale('id')->translatedFormat('d M Y, H:i:s') : 'Tidak tercatat' }}
                </dd>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Mesin Database</dt>
                <dd class="mt-0.5 font-bold text-slate-900 dark:text-white uppercase">{{ $preview['database_engine'] ?? 'mysql' }}</dd>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Lampiran Pribadi Termasuk</dt>
                <dd class="mt-0.5 font-bold {{ $preview['includes_private_files'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400' }}">
                    {{ $preview['includes_private_files'] ? 'Ya, disertakan' : 'Tidak ada lampiran' }}
                </dd>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/60">
                <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400">Integritas Checksum (SHA-256)</dt>
                <dd class="mt-0.5 font-bold {{ $preview['checksum_valid'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                    {{ $preview['checksum_valid'] ? 'Terverifikasi Cocok' : 'Gagal / Tidak Cocok' }}
                </dd>
            </div>
        </dl>
    </div>

    @if(! $preview['is_valid'])
        <div class="section-card accent-rose mt-6">
            <h2 class="font-bold text-rose-950 dark:text-rose-200">Arsip Tidak Dapat Dipulihkan</h2>
            <p class="mt-1 text-sm text-rose-700 dark:text-rose-300">Ditemukan kesalahan pada arsip cadangan ini sehingga proses restore dicegah demi integritas sistem:</p>
            <ul class="mt-3 list-disc list-inside text-xs text-rose-800 dark:text-rose-300 space-y-1">
                @foreach($preview['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <div class="mt-5">
                <a href="{{ route('backups.index') }}" class="btn-secondary">Kembali ke Daftar Backup</a>
            </div>
        </div>
    @else
        <!-- Strong Confirmation Form -->
        <div class="section-card accent-rose mt-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Konfirmasi Pemulihan</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                Untuk mencegah pemulihan yang tidak disengaja, ketik kata <code class="rounded bg-rose-100 px-1.5 py-0.5 font-bold text-rose-800 dark:bg-rose-950 dark:text-rose-200">RESTORE</code> pada kotak di bawah ini:
            </p>

            <form method="POST" action="{{ route('backups.restore', $filename) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="confirm_restore" class="sr-only">Konfirmasi RESTORE</label>
                    <input type="text" id="confirm_restore" name="confirm_restore" autocomplete="off" required placeholder="Ketik RESTORE di sini" class="form-control text-center font-bold tracking-widest text-rose-700 uppercase focus:border-rose-500 focus:ring-rose-500">
                    <x-form-error name="confirm_restore" />
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                    <a href="{{ route('backups.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-danger !bg-rose-600 hover:!bg-rose-700">
                        <x-icon name="clock" size="4" />
                        <span>Mulai Proses Restore Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
