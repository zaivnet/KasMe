@extends('layouts.app')
@section('title', 'Tambah Utang atau Piutang')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('debts.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke utang dan piutang</a>
    <h1 class="page-title mt-4">Tambah utang atau piutang</h1>
    <form method="POST" action="{{ route('debts.store') }}" class="section-card accent-rose mt-6">
        @csrf
        @include('debts._form')
        <button class="btn-primary mt-6">Buat catatan</button>
    </form>
</div>
@endsection
