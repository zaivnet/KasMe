@extends('layouts.app')
@section('title', 'Edit Utang atau Piutang')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('debts.show', $debt) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke catatan</a>
    <h1 class="page-title mt-4">Edit utang atau piutang</h1>
    <form method="POST" action="{{ route('debts.update', $debt) }}" class="section-card accent-rose mt-6">
        @csrf @method('PUT')
        @include('debts._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Arsipkan catatan</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Catatan disembunyikan dari daftar aktif. Riwayat pembayaran dan mutasi saldo akun tetap aman tersimpan.</p>
        <form method="POST" action="{{ route('debts.destroy', $debt) }}" class="mt-4" onsubmit="return confirm('Arsipkan catatan ini? Dampak pembayaran tetap tersimpan dalam riwayat akun.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Arsipkan catatan</button>
        </form>
    </section>
</div>
@endsection
