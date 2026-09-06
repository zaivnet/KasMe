@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
<div class="mx-auto max-w-3xl">
    <header>
        <p class="section-kicker">Preferensi pengguna</p>
        <h1 class="page-title">Pengaturan</h1>
        <p class="page-description">Sesuaikan tampilan aplikasi tanpa mengubah nilai finansial yang telah tersimpan.</p>
    </header>

    <form method="POST" action="{{ route('settings.update') }}" class="section-card accent-emerald mt-8">
        @csrf @method('PUT')
        <section>
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Regional</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Format dan wilayah</h2>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="currency" class="form-label">Mata uang utama</label>
                    <input id="currency" name="currency" value="{{ old('currency', $preferences->currency) }}" required maxlength="3" class="form-control uppercase">
                    <x-form-error name="currency" />
                    <p class="form-helper">Kode ISO tiga huruf, misalnya IDR, USD, atau SGD.</p>
                </div>
                <div>
                    <label for="date_format" class="form-label">Format tanggal</label>
                    <select id="date_format" name="date_format" required class="form-control">
                        @foreach($dateFormats as $value => $label)
                            <option value="{{ $value }}" @selected(old('date_format', $preferences->date_format) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-form-error name="date_format" />
                </div>
                <div class="sm:col-span-2">
                    <label for="timezone" class="form-label">Zona waktu</label>
                    <select id="timezone" name="timezone" required class="form-control">
                        @foreach($timezones as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $preferences->timezone) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                    <x-form-error name="timezone" />
                    <p class="form-helper">Digunakan untuk batas hari, minggu, bulan, dan tahun pada dasbor serta laporan.</p>
                </div>
            </div>
        </section>

        <section class="mt-8 border-t border-slate-100 pt-6 dark:border-slate-800">
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Tampilan</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">Tema aplikasi</h2>
            </div>
            <fieldset>
                <legend class="sr-only">Tema</legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach($themes as $value => $label)
                        <label class="relative flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/90 bg-white/90 p-4 shadow-2xs transition hover:border-emerald-300 hover:bg-emerald-50/40 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/80 has-[:checked]:text-emerald-950 has-[:checked]:ring-2 has-[:checked]:ring-emerald-500/30 dark:border-slate-800 dark:bg-slate-900/90 dark:has-[:checked]:border-emerald-500 dark:has-[:checked]:bg-emerald-950/60 dark:has-[:checked]:text-emerald-200">
                            <input type="radio" name="theme" value="{{ $value }}" @checked(old('theme', $preferences->theme) === $value) required class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-semibold">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <x-form-error name="theme" />
            </fieldset>
        </section>

        <div class="mt-6 rounded-2xl border border-amber-200/80 bg-amber-50/70 p-4 text-xs leading-relaxed text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200 sm:text-sm">
            <strong>Catatan:</strong> Mengganti mata uang hanya mengubah preferensi tampilan dan nilai awal formulir akun baru. Aplikasi tidak melakukan konversi kurs terhadap data lama.
        </div>

        <button class="btn-primary mt-6">Simpan pengaturan</button>
    </form>

    <section class="section-card accent-cyan mt-8">
        <div class="section-heading">
            <span class="icon-badge-cyan"><x-icon name="user" size="5"/></span>
            <div>
                <p class="section-kicker text-teal-700 dark:text-teal-400">Akun</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Profil dan keamanan</h2>
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Kelola nama pengguna, alamat email, dan kata sandi akun Anda dari halaman profil.</p>
        <a href="{{ route('profile.edit') }}" class="btn-secondary mt-4">
            <x-icon name="user" size="4"/>
            <span>Buka profil</span>
        </a>
    </section>

    @can('manage-system-backups')
    <section class="section-card accent-emerald mt-8">
        <div class="section-heading">
            <span class="icon-badge-teal"><x-icon name="cloud-arrow-down" size="5"/></span>
            <div>
                <p class="section-kicker text-teal-700 dark:text-teal-400">Penyimpanan</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Backup & Restore Penuh</h2>
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Cadangkan seluruh database dan berkas lampiran keuangan, atur jadwal otomatis, dan pulihkan sistem sewaktu-waktu.</p>
        <a href="{{ route('backups.index') }}" class="btn-secondary mt-4">
            <x-icon name="cloud-arrow-down" size="4"/>
            <span>Buka Backup & Restore</span>
        </a>
    </section>
    @endcan

    <section class="section-card accent-violet mt-8">
        <div class="section-heading">
            <span class="icon-badge-violet"><x-icon name="report" size="5"/></span>
            <div>
                <p class="section-kicker text-violet-700 dark:text-violet-400">Privasi</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Cadangan data pribadi</h2>
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Unduh salinan data akun Anda sebagai berkas JSON portabel.</p>
        <a href="{{ route('settings.export') }}" class="btn-secondary mt-4">
            <span>Unduh cadangan JSON</span>
        </a>
    </section>
</div>
@endsection
