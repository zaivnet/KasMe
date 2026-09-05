@extends('layouts.guest')
@section('title', 'Lupa Kata Sandi')
@section('content')
<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Lupa kata sandi</h1>
<p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-sm">Masukkan email terdaftar Anda dan kami akan mengirim tautan pemulihan kata sandi yang aman.</p>
<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
    @csrf
    <div>
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="form-control">
        <x-form-error name="email" />
    </div>
    <div class="pt-2">
        <button class="btn-primary w-full">Kirim tautan pengaturan ulang</button>
    </div>
</form>
<a href="{{ route('login') }}" class="mt-6 block text-center text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400 sm:text-sm">Kembali ke halaman masuk</a>
@endsection
