@extends('layouts.app')
@section('title', 'Tambah Tagihan')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('bills.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke tagihan</a>
    <h1 class="page-title mt-4">Tambah tagihan</h1>
    <p class="page-description">Pantau tanggal jatuh tempo dan status pembayaran tanpa mengubah buku kas.</p>
    <form method="POST" action="{{ route('bills.store') }}" class="section-card accent-amber mt-6">
        @csrf
        @include('bills._form')
        <button class="btn-primary mt-6">Buat tagihan</button>
    </form>
</div>
@endsection
