@extends('layouts.app')
@section('title', 'Tambah Anggaran')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('budgets.index', ['month' => $defaultMonth, 'year' => $defaultYear]) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke anggaran</a>
    <h1 class="page-title mt-4">Tambah anggaran bulanan</h1>
    <p class="page-description">Tetapkan satu target pengeluaran untuk setiap kategori per bulan.</p>
    <form method="POST" action="{{ route('budgets.store') }}" class="section-card accent-amber mt-6">
        @csrf
        @include('budgets._form')
        <button @disabled($categories->isEmpty()) class="btn-primary mt-6 disabled:cursor-not-allowed disabled:opacity-50">Buat anggaran</button>
    </form>
</div>
@endsection
