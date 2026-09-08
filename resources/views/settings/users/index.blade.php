@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('content')
<div class="mx-auto max-w-5xl" x-data="{
    resetModalOpen: false,
    resetUserName: '',
    resetActionUrl: '',
    openResetModal(name, url) {
        this.resetUserName = name;
        this.resetActionUrl = url;
        this.resetModalOpen = true;
    }
}">
    <div class="mb-4">
        <a href="{{ route('settings.edit') }}" class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400">&larr; Kembali ke Pengaturan</a>
    </div>

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Administrasi sistem</p>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="page-description">Kelola akses akun, tambahkan pengguna baru secara terkontrol, dan atur kata sandi tanpa membuka registrasi publik.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('settings.users.create') }}" class="btn-primary">
                <x-icon name="plus" size="4"/>
                <span>Tambah Pengguna</span>
            </a>
        </div>
    </header>

    <!-- Info Banner -->
    <div class="mt-6 rounded-2xl border border-blue-200/70 bg-blue-50/60 p-4 text-xs leading-relaxed text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-200 sm:text-sm">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 shrink-0 text-blue-600 dark:text-blue-400"><x-icon name="users" size="5"/></span>
            <div>
                <strong class="font-semibold">Isolasi Data Finansial Mandiri:</strong> Setiap pengguna memiliki buku kas dan data keuangan terpisah secara ketat. Pengguna tidak dapat melihat maupun mengakses transaksi pengguna lain. Registrasi publik tetap ditutup demi keamanan sistem.
            </div>
        </div>
    </div>

    <!-- Users List -->
    <div class="premium-surface mt-8 overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
            Daftar Pengguna Terdaftar ({{ $users->count() }})
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($users as $userItem)
                @php
                    $isSelf = $userItem->id === auth()->id();
                    $isOwner = (bool) $userItem->is_instance_owner;
                    $hasData = (
                        $userItem->accounts_count > 0 ||
                        $userItem->transactions_count > 0 ||
                        $userItem->transfers_count > 0 ||
                        $userItem->budgets_count > 0 ||
                        $userItem->bills_count > 0 ||
                        $userItem->debts_count > 0 ||
                        $userItem->saving_goals_count > 0
                    );
                    $initials = collect(explode(' ', $userItem->name))
                        ->map(fn($part) => mb_substr($part, 0, 1))
                        ->take(2)
                        ->join('');
                @endphp
                <div class="list-row flex min-w-0 flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl text-sm font-bold text-white shadow-xs {{ $isOwner ? 'bg-gradient-to-br from-emerald-600 to-teal-700' : 'bg-gradient-to-br from-blue-500 to-indigo-600' }}">
                            {{ $initials }}
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="truncate font-bold text-slate-900 dark:text-white">{{ $userItem->name }}</h2>
                                @if($isSelf)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Anda</span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $userItem->email }}</p>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                @if($hasData)
                                    <span>Memiliki catatan finansial ({{ $userItem->transactions_count }} transaksi, {{ $userItem->accounts_count }} akun)</span>
                                @else
                                    <span class="text-slate-400">Belum ada catatan transaksi</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <!-- Owner badge -->
                        @if($isOwner)
                            <span class="status-chip-emerald">
                                Instance Owner
                            </span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                Pengguna
                            </span>
                        @endif

                        <!-- Status chip -->
                        @if($userItem->is_active)
                            <span class="status-chip-cyan">Aktif</span>
                        @else
                            <span class="status-chip-rose">Nonaktif</span>
                        @endif

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 sm:ml-2">
                            <a href="{{ route('settings.users.edit', $userItem) }}" class="btn-secondary !min-h-9 !px-3 !py-1.5 text-xs">
                                Edit
                            </a>

                            <button type="button"
                                    @click="openResetModal('{{ addslashes($userItem->name) }}', '{{ route('settings.users.reset-password', $userItem) }}')"
                                    class="btn-secondary !min-h-9 !px-3 !py-1.5 text-xs">
                                Reset Sandi
                            </button>

                            @if(! $isOwner)
                                @if($hasData)
                                    @if($userItem->is_active)
                                        <form method="POST" action="{{ route('settings.users.destroy', $userItem) }}"
                                              data-confirm-title="Nonaktifkan pengguna?"
                                              data-confirm-button="Nonaktifkan"
                                              onsubmit="return confirm('Pengguna ini memiliki riwayat data keuangan. Pengguna tidak dapat dihapus permanen demi menjaga integritas data historis. Akun akan dinonaktifkan sehingga tidak dapat login.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-warning !min-h-9 !px-3 !py-1.5 text-xs">
                                                Nonaktifkan
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('settings.users.update', $userItem) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $userItem->name }}">
                                            <input type="hidden" name="email" value="{{ $userItem->email }}">
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn-secondary !min-h-9 !px-3 !py-1.5 text-xs text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">
                                                Aktifkan
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('settings.users.destroy', $userItem) }}"
                                          data-confirm-title="Hapus pengguna?"
                                          data-confirm-button="Hapus"
                                          onsubmit="return confirm('Pengguna ini belum memiliki data keuangan dan akan dihapus secara permanen dari sistem.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger !min-h-9 !px-3 !py-1.5 text-xs">
                                            Hapus
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

    <!-- Reset Password Modal -->
    <div x-cloak x-show="resetModalOpen" class="fixed inset-0 z-[60] grid place-items-end p-4 sm:place-items-center" role="dialog" aria-modal="true" aria-labelledby="reset-modal-title">
        <div x-show="resetModalOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="resetModalOpen = false"></div>
        <section x-show="resetModalOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                 class="relative w-full max-w-md rounded-3xl border border-slate-200/80 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-950/80 dark:text-blue-400">
                <x-icon name="user" size="6" />
            </div>
            <h2 id="reset-modal-title" class="mt-4 text-xl font-bold tracking-tight text-slate-900 dark:text-white">Reset Kata Sandi</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Tetapkan kata sandi baru untuk <strong class="font-semibold text-slate-800 dark:text-slate-200" x-text="resetUserName"></strong>.
            </p>

            <form method="POST" :action="resetActionUrl" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="modal_password" class="form-label">Kata Sandi Baru</label>
                    <input type="password" id="modal_password" name="password" required minlength="8" placeholder="Minimal 8 karakter" class="form-control">
                    <p class="form-helper">Pengguna dapat menggunakan kata sandi ini untuk login berikutnya.</p>
                </div>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button type="button" @click="resetModalOpen = false" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary !bg-blue-600 hover:!bg-blue-700">Simpan Kata Sandi</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
