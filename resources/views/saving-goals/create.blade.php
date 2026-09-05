@extends('layouts.app')
@section('title', 'Tambah Target Tabungan')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('saving-goals.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke target tabungan</a>
    <h1 class="page-title mt-4">Tambah target tabungan</h1>
    <form method="POST" action="{{ route('saving-goals.store') }}" class="section-card accent-violet mt-6">
        @csrf
        @include('saving-goals._form')
        <button class="btn-primary mt-6">Buat target</button>
    </form>
</div>
@endsection
