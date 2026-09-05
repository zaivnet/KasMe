@extends('layouts.app')
@section('title', 'Edit Target Tabungan')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('saving-goals.show', $goal) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke target tabungan</a>
    <h1 class="page-title mt-4">Edit target tabungan</h1>
    <form method="POST" action="{{ route('saving-goals.update', $goal) }}" class="section-card accent-violet mt-6">
        @csrf @method('PUT')
        @include('saving-goals._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Arsipkan target</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Target disembunyikan dari daftar aktif. Riwayat pergerakan dana dan mutasi saldo akun tetap aman tersimpan.</p>
        <form method="POST" action="{{ route('saving-goals.destroy', $goal) }}" class="mt-4" onsubmit="return confirm('Arsipkan target ini? Dampaknya pada akun tetap tersimpan dalam riwayat.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Arsipkan target</button>
        </form>
    </section>
</div>
@endsection
