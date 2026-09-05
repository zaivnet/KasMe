@extends('layouts.app')
@section('title', 'Edit Transfer')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('transfers.show', $transfer) }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke transfer</a>
    <h1 class="page-title mt-4">Edit transfer</h1>
    <form method="POST" action="{{ route('transfers.update', $transfer) }}" class="section-card accent-cyan mt-6">
        @csrf @method('PUT')
        @include('transfers._form')
        <button class="btn-primary mt-6">Simpan perubahan</button>
    </form>
</div>
@endsection
