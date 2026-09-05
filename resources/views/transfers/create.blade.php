@extends('layouts.app')
@section('title', 'Transfer Baru')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('transfers.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke transfer</a>
    <h1 class="page-title mt-4">Transfer baru</h1>
    @if($accounts->count() < 2)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-300">
            Diperlukan setidaknya dua akun aktif yang tersedia untuk melakukan transfer dana.
        </div>
    @else
        <form method="POST" action="{{ route('transfers.store') }}" class="section-card accent-cyan mt-6">
            @csrf
            @include('transfers._form')
            <button class="btn-primary mt-6">Buat transfer</button>
        </form>
    @endif
</div>
@endsection
