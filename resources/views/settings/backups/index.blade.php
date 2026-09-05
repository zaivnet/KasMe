@extends('layouts.app')
@section('title', 'Backup & Restore')
@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-4">
        <a href="{{ route('settings.edit') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke Pengaturan</a>
    </div>

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Keamanan data</p>
            <h1 class="page-title">Backup & Restore</h1>
            <p class="page-description">Kelola pencadangan data finansial, unduh arsip secara aman, atur jadwal otomatis, dan pulihkan data kapan saja.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="POST" action="{{ route('backups.store') }}">
                @csrf
                <button type="submit" class="btn-primary">
                    <x-icon name="cloud-arrow-down" size="4"/>
                    <span>Buat Backup Sekarang</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Summary Metrics -->
    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <p class="stat-label">Cadangan Terakhir</p>
                <span class="icon-badge-teal"><x-icon name="clock" size="4"/></span>
            </div>
            <p class="stat-value mt-2 text-lg font-bold">
                {{ $setting->last_backup_at ? $setting->last_backup_at->locale('id')->diffForHumans() : 'Belum pernah' }}
            </p>
            <p class="stat-meta text-xs">
                @if($setting->last_backup_status === 'success')
                    <span class="text-emerald-700 font-semibold dark:text-emerald-400">&bull; Berhasil diproses</span>
                @elseif($setting->last_backup_status === 'failed')
                    <span class="text-rose-700 font-semibold dark:text-rose-400">&bull; Terakhir gagal</span>
                @else
                    <span class="text-slate-500">&bull; Siap dicadangkan</span>
                @endif
            </p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <p class="stat-label">Pencadangan Otomatis</p>
                <span class="icon-badge-cyan"><x-icon name="calendar" size="4"/></span>
            </div>
            <p class="stat-value mt-2 text-lg font-bold">
                {{ $setting->backup_schedule_enabled ? 'Aktif' : 'Nonaktif' }}
            </p>
            <p class="stat-meta text-xs">
                @if($setting->backup_schedule_enabled)
                    {{ $frequencies[$setting->backup_schedule_frequency] ?? 'Harian' }} pukul {{ $setting->backup_schedule_time }}
                @else
                    Pencadangan terjadwal dimatikan
                @endif
            </p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <p class="stat-label">Penggunaan Penyimpanan</p>
                <span class="icon-badge-violet"><x-icon name="folder" size="4"/></span>
            </div>
            <p class="stat-value mt-2 text-lg font-bold">
                {{ $storageUsage }}
            </p>
            <p class="stat-meta text-xs">
                {{ count($backups) }} berkas arsip tersimpan aman
            </p>
        </div>
    </div>

    <!-- Upload Backup Form -->
    <div class="section-card accent-cyan mt-8">
        <div class="section-heading">
            <span class="icon-badge-cyan"><x-icon name="cloud-arrow-up" size="5"/></span>
            <div>
                <p class="section-kicker text-teal-700 dark:text-teal-400">Unggah arsip</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Unggah Berkas Backup</h2>
            </div>
        </div>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Unggah berkas arsip ZIP cadangan KasMe untuk diverifikasi dan dipulihkan.</p>
        <form method="POST" action="{{ route('backups.upload') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            @csrf
            <div class="flex-1">
                <input type="file" name="backup_file" accept=".zip" required class="form-control text-xs sm:text-sm">
                <x-form-error name="backup_file" />
            </div>
            <button type="submit" class="btn-secondary whitespace-nowrap">
                <x-icon name="cloud-arrow-up" size="4"/>
                <span>Unggah & Verifikasi</span>
            </button>
        </form>
    </div>

    <!-- Scheduled Backup Settings -->
    <div class="section-card accent-emerald mt-8">
        <div class="section-heading">
            <span class="icon-badge-emerald"><x-icon name="clock" size="5"/></span>
            <div>
                <p class="section-kicker text-emerald-700 dark:text-emerald-400">Otomasi</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Jadwal Pencadangan Otomatis & Retensi</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('backups.updateSchedule') }}" class="mt-5 space-y-5">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3">
                <input type="hidden" name="backup_schedule_enabled" value="0">
                <input type="checkbox" id="backup_schedule_enabled" name="backup_schedule_enabled" value="1" @checked(old('backup_schedule_enabled', $setting->backup_schedule_enabled)) class="size-4.5 rounded text-emerald-600 focus:ring-emerald-500">
                <label for="backup_schedule_enabled" class="text-sm font-semibold text-slate-900 dark:text-white cursor-pointer">
                    Aktifkan pencadangan otomatis terjadwal
                </label>
            </div>
            <x-form-error name="backup_schedule_enabled" />

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="backup_schedule_frequency" class="form-label">Frekuensi</label>
                    <select id="backup_schedule_frequency" name="backup_schedule_frequency" class="form-control">
                        @foreach($frequencies as $key => $label)
                            <option value="{{ $key }}" @selected(old('backup_schedule_frequency', $setting->backup_schedule_frequency) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-form-error name="backup_schedule_frequency" />
                </div>

                <div>
                    <label for="backup_schedule_time" class="form-label">Waktu Eksekusi (JJ:MM)</label>
                    <input type="text" id="backup_schedule_time" name="backup_schedule_time" value="{{ old('backup_schedule_time', $setting->backup_schedule_time ?? '02:00') }}" placeholder="02:00" class="form-control" maxlength="5">
                    <x-form-error name="backup_schedule_time" />
                    <p class="form-helper">Waktu setempat server.</p>
                </div>

                <div>
                    <label for="backup_retention" class="form-label">Kebijakan Retensi</label>
                    <select id="backup_retention" name="backup_retention" class="form-control">
                        @foreach($retentions as $count => $label)
                            <option value="{{ $count }}" @selected((int) old('backup_retention', $setting->backup_retention ?? 7) === $count)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-form-error name="backup_retention" />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/90 bg-slate-50/60 p-4 text-xs leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300">
                <p class="font-bold text-slate-800 dark:text-slate-200">Panduan cPanel Cron Job:</p>
                <p class="mt-1">Pencadangan terjadwal dijalankan melalui scheduler Laravel. Pada hosting cPanel, pastikan cron job berikut telah aktif:</p>
                <code class="mt-1.5 block overflow-x-auto rounded-lg bg-slate-950 p-2 text-emerald-400 font-mono text-xs">
                    * * * * * cd /home/USERNAME/apps/kasme && php artisan schedule:run >> /dev/null 2>&1
                </code>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">
                    <span>Simpan Pengaturan Jadwal</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Backup History List -->
    <div class="mt-8">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Riwayat Berkas Cadangan</h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ count($backups) }} berkas</span>
        </div>

        @if(empty($backups))
            <div class="section-card mt-4 text-center py-10">
                <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-teal-50 text-teal-700 dark:bg-teal-950/80 dark:text-teal-400">
                    <x-icon name="folder" size="6" />
                </div>
                <h3 class="mt-3 font-bold text-slate-900 dark:text-white">Belum ada berkas cadangan</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Buat backup pertama Anda sekarang untuk melindungi data finansial.</p>
                <form method="POST" action="{{ route('backups.store') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <x-icon name="cloud-arrow-down" size="4"/>
                        <span>Buat Backup Pertama</span>
                    </button>
                </form>
            </div>
        @else
            <div class="premium-surface mt-4 overflow-hidden">
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($backups as $b)
                        @php
                            $typeLabel = match($b['type']) {
                                'scheduled' => 'Terjadwal',
                                'pre_restore' => 'Pra-Restore',
                                default => 'Manual',
                            };
                            $typeChipClass = match($b['type']) {
                                'scheduled' => 'status-chip-emerald',
                                'pre_restore' => 'status-chip-amber',
                                default => 'status-chip-cyan',
                            };
                        @endphp
                        <div class="list-row flex min-w-0 flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="flex min-w-0 items-center gap-3.5">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-700 shadow-xs dark:bg-teal-950 dark:text-teal-400">
                                    <x-icon name="folder" size="5" />
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate font-bold text-slate-900 dark:text-white text-sm sm:text-base">{{ $b['filename'] }}</h3>
                                        <span class="{{ $typeChipClass }} text-[11px]">{{ $typeLabel }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        Dibuat pada {{ $b['created_at']->locale('id')->translatedFormat('d M Y, H:i') }} &bull; {{ $b['formatted_size'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                                <a href="{{ route('backups.download', $b['filename']) }}" class="btn-secondary !min-h-9 !px-3 !py-1.5 text-xs" title="Unduh arsip cadangan">
                                    <x-icon name="cloud-arrow-down" size="4" />
                                    <span>Unduh</span>
                                </a>

                                <a href="{{ route('backups.restorePreview', $b['filename']) }}" class="btn-warning !min-h-9 !px-3 !py-1.5 text-xs" title="Tinjau dan pulihkan data dari arsip ini">
                                    <x-icon name="clock" size="4" />
                                    <span>Restore</span>
                                </a>

                                <form method="POST" action="{{ route('backups.destroy', $b['filename']) }}"
                                      data-confirm-title="Hapus berkas cadangan?"
                                      data-confirm-button="Hapus"
                                      onsubmit="return confirm('Hapus berkas cadangan ini secara permanen dari server?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger !min-h-9 !px-3 !py-1.5 text-xs" title="Hapus arsip">
                                        <x-icon name="trash" size="4" />
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
