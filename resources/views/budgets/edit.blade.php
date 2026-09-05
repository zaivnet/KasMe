@extends('layouts.app')
@section('title', 'Edit Anggaran')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('budgets.index', ['month' => $budget->month, 'year' => $budget->year]) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke anggaran</a>
    <h1 class="page-title mt-4">Edit anggaran bulanan</h1>
    <form method="POST" action="{{ route('budgets.update', $budget) }}" class="section-card accent-amber mt-6">
        @csrf @method('PUT')
        @include('budgets._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Hapus anggaran</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Hanya target yang dihapus; seluruh riwayat transaksi tetap aman tersimpan.</p>
        <form method="POST" action="{{ route('budgets.destroy', $budget) }}" class="mt-4" onsubmit="return confirm('Hapus anggaran ini? Transaksi tidak akan berubah.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Hapus anggaran</button>
        </form>
    </section>
</div>
@endsection
