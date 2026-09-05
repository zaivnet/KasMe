@extends('layouts.app')
@section('title', 'Edit Pergerakan Dana')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('saving-goals.show', $goal) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke target tabungan</a>
    <h1 class="page-title mt-4">Edit pergerakan dana</h1>
    <form method="POST" action="{{ route('saving-goals.transactions.update', [$goal, $transaction]) }}" class="section-card accent-violet mt-6">
        @csrf @method('PUT')
        @include('saving-goals.transactions._form')
        <button class="btn-primary mt-6">Simpan pergerakan</button>
    </form>
    <section class="section-card accent-rose mt-6">
        <h2 class="font-bold text-slate-900 dark:text-white">Batalkan pergerakan</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pembatalan ditolak jika membuat total saldo progres target menjadi negatif.</p>
        <form method="POST" action="{{ route('saving-goals.transactions.destroy', [$goal, $transaction]) }}" class="mt-4" onsubmit="return confirm('Batalkan pergerakan ini? Dampaknya pada akun juga akan dihapus.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Batalkan pergerakan</button>
        </form>
    </section>
</div>
@endsection
