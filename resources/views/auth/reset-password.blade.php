@extends('layouts.guest')
@section('title', 'Atur Ulang Kata Sandi')
@section('content')
<h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Atur ulang kata sandi</h1>
<p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">Buat kata sandi baru yang aman untuk akun KasMe Anda.</p>
<form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email" class="form-control">
        <x-form-error name="email" />
    </div>
    <div>
        <label for="password" class="form-label">Kata sandi baru</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" class="form-control">
        <x-form-error name="password" />
    </div>
    <div>
        <label for="password_confirmation" class="form-label">Konfirmasi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control">
    </div>
    <div class="pt-2">
        <button class="btn-primary w-full">Atur ulang kata sandi</button>
    </div>
</form>
@endsection
