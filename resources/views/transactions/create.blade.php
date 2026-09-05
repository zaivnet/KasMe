@extends('layouts.app')
@section('title', 'Tambah Transaksi')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke transaksi</a>
    <h1 class="page-title mt-4">Tambah transaksi</h1>
    @if($accounts->isEmpty())
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-300">
            Buat akun sebelum mencatat transaksi.
        </div>
    @else
        <form method="POST" action="{{ route('transactions.store') }}" enctype="multipart/form-data" class="section-card accent-violet mt-6">
            @csrf
            @include('transactions._form')
            <button class="btn-primary mt-6">Buat transaksi</button>
        </form>
    @endif
</div>
@endsection
