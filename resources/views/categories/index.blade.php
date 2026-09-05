@extends('layouts.app')
@section('title', 'Kategori')
@section('content')
<div class="mx-auto max-w-6xl">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Klasifikasi transaksi</p>
            <h1 class="page-title">Kategori</h1>
            <p class="page-description">Kelola kategori pemasukan dan pengeluaran beserta hierarki subkategori.</p>
        </div>
        <a href="{{ route('categories.create') }}" class="btn-primary">
            <x-icon name="plus" size="4"/>
            <span>Tambah kategori</span>
        </a>
    </header>

    <form method="GET" action="{{ route('categories.index') }}" class="filter-surface mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="type" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Jenis</label>
            <select id="type" name="type" class="form-control">
                <option value="">Semua jenis</option>
                <option value="income" @selected(($filters['type'] ?? '') === 'income')>Pemasukan</option>
                <option value="expense" @selected(($filters['type'] ?? '') === 'expense')>Pengeluaran</option>
            </select>
        </div>
        <div class="flex-1">
            <label for="status" class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="">Semua status</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Tidak aktif</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button class="btn-secondary">Terapkan filter</button>
            <a href="{{ route('categories.index') }}" class="btn-ghost">Atur ulang</a>
        </div>
    </form>

    @if($categories->isEmpty())
        <x-empty-state class="mt-8" icon="category" title="Kategori tidak ditemukan" description="Buat kategori pemasukan atau pengeluaran, atau sesuaikan filter." :action="route('categories.create')" action-label="Tambah kategori" accent="amber" />
    @else
        <div class="premium-surface mt-8 overflow-hidden">
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($categories as $category)
                    @php
                        $color = $category->color ?: ($category->type === 'income' ? '#059669' : '#e11d48');
                        $isUsed = $category->isUsed();
                    @endphp
                    <div class="list-row flex min-w-0 flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl text-white shadow-xs" style="background-color: {{ $color }}">
                                <x-icon :name="$category->icon ?: 'category'" size="5" />
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate font-bold text-slate-900 dark:text-white">{{ $category->name }}</h2>
                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                    {{ $category->parent ? 'Subkategori dari '.$category->parent->name : 'Kategori utama' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="{{ $category->type === 'income' ? 'status-chip-emerald' : 'status-chip-rose' }}">
                                {{ App\Models\Category::TYPES[$category->type] }}
                            </span>
                            <span class="{{ $category->is_active ? 'status-chip-cyan' : 'status-chip-muted' }}">
                                {{ $category->is_active ? 'Aktif' : 'Tidak aktif' }}
                            </span>

                            <div class="flex items-center gap-1.5 sm:ml-2">
                                <a href="{{ route('categories.edit', $category) }}" class="btn-secondary !min-h-9 !px-3 !py-1.5 text-xs">
                                    Edit
                                </a>
                                @if(! $isUsed)
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                          data-confirm-title="Hapus kategori?"
                                          data-confirm-button="Hapus"
                                          onsubmit="return confirm('Kategori ini belum digunakan dan akan dihapus permanen.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger !min-h-9 !px-3 !py-1.5 text-xs">
                                            Hapus
                                        </button>
                                    </form>
                                @else
                                    @if($category->is_active)
                                        <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                              data-confirm-title="Nonaktifkan kategori?"
                                              data-confirm-button="Nonaktifkan"
                                              onsubmit="return confirm('Kategori ini sudah digunakan pada data keuangan. Kategori tidak dapat dihapus permanen agar histori tetap utuh. Kategori akan dinonaktifkan dan tidak muncul untuk transaksi baru.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-warning !min-h-9 !px-3 !py-1.5 text-xs">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('categories.update', $category) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $category->name }}">
                                            <input type="hidden" name="type" value="{{ $category->type }}">
                                            <input type="hidden" name="color" value="{{ $category->color }}">
                                            <input type="hidden" name="icon" value="{{ $category->icon }}">
                                            <input type="hidden" name="parent_id" value="{{ $category->parent_id }}">
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn-secondary !min-h-9 !px-3 !py-1.5 text-xs text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">
                                                Aktifkan
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="mt-8">{{ $categories->links() }}</div>
    @endif
</div>
@endsection
