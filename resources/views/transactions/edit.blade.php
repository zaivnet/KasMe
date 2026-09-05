@extends('layouts.app')
@section('title', 'Edit Transaksi')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('transactions.show', $transaction) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke transaksi</a>
    <h1 class="page-title mt-4">Edit transaksi</h1>
    <form method="POST" action="{{ route('transactions.update', $transaction) }}" enctype="multipart/form-data" class="section-card accent-violet mt-6">
        @csrf
        @method('PUT')
        @include('transactions._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
</div>
@endsection
