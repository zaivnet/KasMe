@extends('layouts.guest')
@section('title', 'Daftar')
@section('content')
<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Buat akun</h1>
<p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Mulai kelola keuangan pribadi Anda bersama KasMe.</p>
<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label for="name" class="form-label">Nama lengkap</label>
        <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control">
        <x-form-error name="name" />
    </div>
    <div>
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="form-control">
        <x-form-error name="email" />
    </div>
    <div>
        <label for="password" class="form-label">Kata sandi</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" class="form-control">
        <x-form-error name="password" />
    </div>
    <div>
        <label for="password_confirmation" class="form-label">Konfirmasi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control">
    </div>
    <div class="pt-2">
        <button class="btn-primary w-full">Daftar</button>
    </div>
</form>
<p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
    Sudah terdaftar? <a href="{{ route('login') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-400">Masuk</a>
</p>
@endsection
