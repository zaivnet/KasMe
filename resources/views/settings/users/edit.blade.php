@extends('layouts.app')
@section('title', 'Edit Pengguna — ' . $user->name)
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('settings.users.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke Manajemen Pengguna</a>
    <header class="mt-4">
        <p class="section-kicker">Administrasi sistem</p>
        <h1 class="page-title">Edit Data Pengguna</h1>
        <p class="page-description">Perbarui informasi profil atau status keaktifan akun {{ $user->name }}.</p>
    </header>

    <form method="POST" action="{{ route('settings.users.update', $user) }}" class="section-card accent-blue mt-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="form-control">
            <x-form-error name="name" />
        </div>

        <div>
            <label for="email" class="form-label">Alamat Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="form-control">
            <x-form-error name="email" />
            <p class="form-helper">Digunakan sebagai kredensial login pengguna.</p>
        </div>

        @if($user->is_instance_owner)
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 p-4 text-xs leading-relaxed text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200 sm:text-sm">
                <div class="flex items-center gap-2">
                    <span class="font-bold">Akun Instance Owner:</span> Status keaktifan akun pemilik utama selalu aktif dan tidak dapat dinonaktifkan.
                </div>
            </div>
        @else
            <div class="pt-2">
                <label class="relative flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200/90 bg-white/90 p-4 shadow-2xs transition hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900/90">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="mt-1 h-4 w-4 rounded text-blue-600 focus:ring-blue-500">
                    <div class="text-sm">
                        <span class="font-semibold text-slate-900 dark:text-white">Status Akun Aktif</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Jika dinonaktifkan, pengguna tidak dapat masuk ke sistem dan sesi aktif akan langsung dihentikan.</p>
                    </div>
                </label>
            </div>
        @endif

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('settings.users.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary !bg-blue-600 hover:!bg-blue-700">Simpan Perubahan</button>
        </div>
    </form>

    <!-- Reset Password Section -->
    <section class="section-card accent-slate mt-8">
        <div class="section-heading">
            <span class="icon-badge-blue"><x-icon name="user" size="5"/></span>
            <div>
                <p class="section-kicker text-blue-700 dark:text-blue-400">Keamanan Akses</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Atur Ulang Kata Sandi</h2>
            </div>
        </div>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Tetapkan kata sandi baru untuk akun pengguna ini tanpa perlu mengetahui kata sandi lamanya.
        </p>
        <form method="POST" action="{{ route('settings.users.reset-password', $user) }}" class="mt-4 flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-1">
                <input type="password" name="password" required minlength="8" placeholder="Kata sandi baru (min. 8 karakter)" class="form-control">
                <x-form-error name="password" />
            </div>
            <button type="submit" class="btn-secondary shrink-0">
                <span>Perbarui Kata Sandi</span>
            </button>
        </form>
    </section>
</div>
@endsection
