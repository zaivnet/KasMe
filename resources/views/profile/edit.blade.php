@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <header>
        <p class="section-kicker">Identitas pengguna</p>
        <h1 class="page-title">Profil</h1>
        <p class="page-description">Kelola informasi identitas akun pribadi dan kredensial keamanan Anda.</p>
    </header>

    <section class="section-card accent-cyan">
        <div class="section-heading">
            <span class="icon-badge-cyan"><x-icon name="user" size="5"/></span>
            <div>
                <p class="section-kicker text-teal-700 dark:text-teal-400">Akun</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Informasi profil</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
            @csrf @method('PATCH')
            <div>
                <label for="name" class="form-label">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="form-control">
                <x-form-error name="name" />
            </div>
            <div>
                <label for="email" class="form-label">Alamat email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="form-control">
                <x-form-error name="email" />
            </div>
            <div class="pt-2">
                <button class="btn-primary">Simpan profil</button>
            </div>
        </form>
    </section>

    <section class="section-card accent-violet">
        <div class="section-heading">
            <span class="icon-badge-violet"><x-icon name="settings" size="5"/></span>
            <div>
                <p class="section-kicker text-violet-700 dark:text-violet-400">Keamanan</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">Perbarui kata sandi</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.password') }}" class="mt-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label for="current_password" class="form-label">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="form-control">
                <x-form-error name="current_password" bag="updatePassword" />
            </div>
            <div>
                <label for="new_password" class="form-label">Kata sandi baru</label>
                <input id="new_password" name="password" type="password" required autocomplete="new-password" class="form-control">
                <x-form-error name="password" bag="updatePassword" />
            </div>
            <div>
                <label for="password_confirmation" class="form-label">Konfirmasi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control">
            </div>
            <div class="pt-2">
                <button class="btn-primary">Perbarui kata sandi</button>
            </div>
        </form>
    </section>
</div>
@endsection
