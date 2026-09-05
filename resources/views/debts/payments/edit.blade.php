@extends('layouts.app')
@section('title', 'Edit Pembayaran')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('debts.show', $debt) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke {{ $debt->person_name }}</a>
    <h1 class="page-title mt-4">Edit pembayaran</h1>
    <p class="page-description">Perubahan direkonsiliasi terhadap jumlah tersisa dan saldo akun yang dipilih.</p>
    <form method="POST" action="{{ route('debts.payments.update', [$debt, $payment]) }}" class="section-card accent-emerald mt-6">
        @csrf @method('PUT')
        @include('debts.payments._form')
        <button class="btn-primary mt-6">Simpan pembayaran</button>
    </form>
    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Batalkan pembayaran</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Catatan pembayaran dan dampaknya pada akun akan dibatalkan secara atomik.</p>
        <form method="POST" action="{{ route('debts.payments.destroy', [$debt, $payment]) }}" class="mt-4" onsubmit="return confirm('Batalkan pembayaran ini? Dampaknya pada akun akan dihapus dan jumlah tersisa dipulihkan.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Batalkan pembayaran</button>
        </form>
    </section>
</div>
@endsection
