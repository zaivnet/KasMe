@extends('layouts.app')
@section('title', 'Edit Akun')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('accounts.show', $account) }}" class="text-sm text-emerald-700 hover:underline dark:text-emerald-400">&larr; Kembali ke akun</a>
    <h1 class="mt-4 text-3xl font-semibold tracking-tight">Edit akun</h1>
    <form method="POST" action="{{ route('accounts.update', $account) }}" class="section-card accent-cyan mt-6">@csrf @method('PUT')
        @include('accounts._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
</div>
@endsection
