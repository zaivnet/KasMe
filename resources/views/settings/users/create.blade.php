@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('settings.users.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke Manajemen Pengguna</a>
    <header class="mt-4">
        <p class="section-kicker">Administrasi sistem</p>
        <h1 class="page-title">Tambah Pengguna Baru</h1>
        <p class="page-description">Daftarkan pengguna baru secara internal. Pengguna baru memiliki buku kas terisolasi dan tidak berstatus Instance Owner.</p>
    </header>

    <form method="POST" action="{{ route('settings.users.store') }}" class="section-card accent-blue mt-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus maxlength="255" class="form-control" placeholder="Contoh: Budi Santoso">
            <x-form-error name="name" />
        </div>

        <div>
            <label for="email" class="form-label">Alamat Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255" class="form-control" placeholder="nama@email.com">
            <x-form-error name="email" />
            <p class="form-helper">Digunakan sebagai kredensial login pengguna.</p>
        </div>

        <div>
            <label for="password" class="form-label">Kata Sandi Awal</label>
            <input type="password" id="password" name="password" required minlength="8" class="form-control" placeholder="Minimal 8 karakter">
            <x-form-error name="password" />
            <p class="form-helper">Berikan kata sandi ini kepada pengguna untuk akses login pertama.</p>
        </div>

        <div class="pt-2">
            <label class="relative flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200/90 bg-white/90 p-4 shadow-2xs transition hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900/90">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="mt-1 h-4 w-4 rounded text-blue-600 focus:ring-blue-500">
                <div class="text-sm">
                    <span class="font-semibold text-slate-900 dark:text-white">Status Akun Aktif</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pengguna dapat langsung masuk ke aplikasi jika opsi ini dicentang.</p>
                </div>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('settings.users.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary !bg-blue-600 hover:!bg-blue-700">Simpan Pengguna</button>
        </div>
    </form>
</div>
@endsection
