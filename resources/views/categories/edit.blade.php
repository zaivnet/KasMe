@extends('layouts.app')
@section('title', 'Edit Kategori')
@section('content')
<div class="mx-auto max-w-3xl">
    <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke kategori</a>
    <h1 class="page-title mt-4">Edit kategori</h1>
    <form method="POST" action="{{ route('categories.update', $category) }}" class="section-card accent-emerald mt-6">
        @csrf @method('PUT')
        @include('categories._form')
        <div class="mt-6 flex flex-wrap gap-3">
            <button class="btn-primary">Simpan perubahan</button>
        </div>
    </form>
    @if($category->isUsed())
        @if($category->is_active)
            <section class="section-card accent-amber mt-6">
                <h2 class="font-bold text-slate-900 dark:text-white">Nonaktifkan kategori</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kategori ini sudah digunakan pada data keuangan. Kategori tidak dapat dihapus permanen agar histori tetap utuh. Kategori akan dinonaktifkan dan tidak muncul untuk transaksi baru.</p>
                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-4"
                      data-confirm-title="Nonaktifkan kategori?"
                      data-confirm-button="Nonaktifkan"
                      onsubmit="return confirm('Kategori ini sudah digunakan pada data keuangan. Kategori tidak dapat dihapus permanen agar histori tetap utuh. Kategori akan dinonaktifkan dan tidak muncul untuk transaksi baru.')">
                    @csrf @method('DELETE')
                    <button class="btn-warning">Nonaktifkan kategori</button>
                </form>
            </section>
        @else
            <section class="section-card accent-emerald mt-6">
                <h2 class="font-bold text-slate-900 dark:text-white">Aktifkan kembali kategori</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kategori ini sedang nonaktif. Aktifkan kembali agar dapat dipilih saat mencatat transaksi baru.</p>
                <form method="POST" action="{{ route('categories.update', $category) }}" class="mt-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $category->name }}">
                    <input type="hidden" name="type" value="{{ $category->type }}">
                    <input type="hidden" name="color" value="{{ $category->color }}">
                    <input type="hidden" name="icon" value="{{ $category->icon }}">
                    <input type="hidden" name="parent_id" value="{{ $category->parent_id }}">
                    <input type="hidden" name="is_active" value="1">
                    <button class="btn-primary">Aktifkan kategori</button>
                </form>
            </section>
        @endif
    @else
        <section class="section-card accent-rose mt-6">
            <h2 class="font-bold text-slate-900 dark:text-white">Hapus kategori</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kategori ini belum digunakan dan akan dihapus permanen.</p>
            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-4"
                  data-confirm-title="Hapus kategori?"
                  data-confirm-button="Hapus"
                  onsubmit="return confirm('Kategori ini belum digunakan dan akan dihapus permanen.')">
                @csrf @method('DELETE')
                <button class="btn-danger">Hapus kategori</button>
            </form>
        </section>
    @endif
</div>
@endsection
