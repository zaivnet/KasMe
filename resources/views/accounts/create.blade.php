@extends('layouts.app')
@section('title', 'Tambah Akun')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('accounts.index') }}" class="text-sm text-emerald-700 hover:underline dark:text-emerald-400">&larr; Kembali ke akun</a>
    <h1 class="mt-4 text-3xl font-semibold tracking-tight">Tambah akun</h1>
    <form method="POST" action="{{ route('accounts.store') }}" class="section-card accent-cyan mt-6">@csrf
        @include('accounts._form')
        <button class="btn-primary mt-6">Buat akun</button>
    </form>
</div>
@endsection
