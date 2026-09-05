@extends('layouts.app')
@section('title', 'Edit Tagihan')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('bills.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke tagihan</a>
    <h1 class="page-title mt-4">Edit tagihan</h1>
    <form method="POST" action="{{ route('bills.update', $bill) }}" class="section-card accent-amber mt-6">
        @csrf @method('PUT')
        @include('bills._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Arsipkan tagihan</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tagihan disembunyikan tetapi tetap tersimpan sebagai riwayat. Transaksi keuangan tidak berubah.</p>
        <form method="POST" action="{{ route('bills.destroy', $bill) }}" class="mt-4" onsubmit="return confirm('Arsipkan tagihan ini? Transaksi tidak akan berubah.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Arsipkan tagihan</button>
        </form>
    </section>
</div>
@endsection
