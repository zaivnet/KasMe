@extends('layouts.guest')
@section('title', 'Masuk')
@section('content')
<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Masuk</h1>
<p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Akses ruang pengelolaan keuangan pribadi Anda dengan aman.</p>
<form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="form-control">
        <x-form-error name="email" />
    </div>
    <div>
        <div class="flex items-center justify-between">
            <label for="password" class="form-label">Kata sandi</label>
            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Lupa kata sandi?</a>
        </div>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="form-control">
        <x-form-error name="password" />
    </div>
    <div class="pt-1">
        <label class="flex items-center gap-2.5 text-sm font-medium text-slate-600 dark:text-slate-300">
            <input name="remember" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <span>Ingat saya</span>
        </label>
    </div>
    <div class="pt-2">
        <button class="btn-primary w-full">Masuk</button>
    </div>
</form>
<p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
    Belum memiliki akun? <a href="{{ route('register') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-400">Daftar</a>
</p>
@endsection
