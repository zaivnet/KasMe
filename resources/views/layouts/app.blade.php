<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scheme-light dark:scheme-dark {{ ($preferences->theme ?? 'system') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#059669">
    <meta name="description" content="Kelola keuangan pribadi Anda dengan aman, rapi, dan terstruktur bersama KasMe.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>if (@js(($preferences->theme ?? 'system') === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');</script>
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
@php
    $navigation = [
        'Utama' => [
            ['dashboard', 'dashboard', 'Dasbor'],
            ['report', 'reports.*', 'Laporan'],
        ],
        'Keuangan' => [
            ['wallet', 'accounts.*', 'Akun'],
            ['category', 'categories.*', 'Kategori'],
            ['transaction', 'transactions.*', 'Transaksi'],
            ['transfer', 'transfers.*', 'Transfer'],
        ],
        'Perencanaan' => [
            ['budget', 'budgets.*', 'Anggaran'],
            ['bill', 'bills.*', 'Tagihan'],
            ['debt', 'debts.*', 'Utang & Piutang'],
            ['goal', 'saving-goals.*', 'Target Tabungan'],
        ],
        'Sistem' => [
            ['settings', 'settings.*', 'Pengaturan'],
            ['user', 'profile.*', 'Profil'],
        ],
    ];
    $routeNames = [
        'dashboard' => 'dashboard',
        'report' => 'reports.index',
        'wallet' => 'accounts.index',
        'category' => 'categories.index',
        'transaction' => 'transactions.index',
        'transfer' => 'transfers.index',
        'budget' => 'budgets.index',
        'bill' => 'bills.index',
        'debt' => 'debts.index',
        'goal' => 'saving-goals.index',
        'settings' => 'settings.edit',
        'user' => 'profile.edit',
    ];
    $user = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
<a href="#main-content" class="fixed left-4 top-4 z-[60] -translate-y-20 rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-transform focus:translate-y-0">Lewati ke konten</a>
<div x-data="{ collapsed: localStorage.getItem('sidebar-collapsed') === 'true', userMenu: false, mobileAccountMenu: false, moreSheet: false, confirmOpen: false, confirmTitle: 'Konfirmasi tindakan', confirmMessage: '', confirmButton: 'Lanjutkan', confirmForm: null }"
     x-effect="document.body.classList.toggle('overflow-hidden', moreSheet || mobileAccountMenu || confirmOpen)"
     @confirm-action.window="confirmTitle = $event.detail.title || 'Konfirmasi tindakan'; confirmMessage = $event.detail.message; confirmButton = $event.detail.button || 'Lanjutkan'; confirmForm = $event.detail.form; confirmOpen = true"
     @keydown.escape.window="userMenu = false; mobileAccountMenu = false; moreSheet = false; confirmOpen = false"
     :class="collapsed ? 'lg:grid-cols-[5.25rem_1fr]' : 'lg:grid-cols-[16rem_1fr]'" class="min-h-screen transition-[grid-template-columns] duration-300 lg:grid">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-teal-100/80 bg-gradient-to-b from-white via-teal-50/20 to-white px-3 py-4 shadow-[4px_0_30px_-24px_rgba(13,148,136,.3)] backdrop-blur-md dark:border-teal-950/60 dark:from-slate-900 dark:via-teal-950/20 dark:to-slate-900 lg:flex" :class="collapsed ? '!w-[5.25rem]' : 'w-64'" aria-label="Navigasi utama">
        <div class="flex h-12 items-center gap-3 px-2" :class="collapsed && 'justify-center'">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden" aria-label="{{ config('app.name') }}">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 text-sm font-bold text-white shadow-md shadow-teal-700/25 ring-1 ring-white/50">K</span>
                <span x-show="!collapsed" x-transition.opacity class="truncate text-base font-bold tracking-tight text-slate-900 dark:text-white">KasMe</span>
            </a>
        </div>
        <nav class="mt-6 flex-1 space-y-5 overflow-y-auto overflow-x-hidden pb-4">
            @foreach($navigation as $group => $items)
            <section>
                <p x-show="!collapsed" class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-400 dark:text-slate-500">{{ $group }}</p>
                <div class="space-y-1">
                    @foreach($items as [$icon, $pattern, $label])
                    <a href="{{ route($routeNames[$icon]) }}" title="{{ $label }}" class="nav-item {{ request()->routeIs($pattern) ? 'nav-item-active' : '' }}" :class="collapsed && 'justify-center px-0'">
                        <x-icon :name="$icon" class="nav-icon" />
                        <span x-show="!collapsed" x-transition.opacity class="whitespace-nowrap">{{ $label }}</span>
                    </a>
                    @endforeach
                </div>
            </section>
            @endforeach
        </nav>
        <button type="button" @click="collapsed = !collapsed; localStorage.setItem('sidebar-collapsed', collapsed)" class="flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm text-slate-500 transition hover:bg-teal-50/60 hover:text-teal-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200" :class="collapsed && 'justify-center px-0'" :aria-label="collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'">
            <x-icon name="chevron" class="transition-transform" ::class="collapsed ? '' : 'rotate-180'"/>
            <span x-show="!collapsed">Ciutkan sidebar</span>
        </button>
    </aside>

    <div class="min-w-0" :class="collapsed ? 'lg:col-start-2' : 'lg:col-start-2'">
        <header class="sticky top-0 z-30 flex h-16 items-center border-b border-teal-100/70 bg-white/90 px-4 shadow-[0_4px_24px_-16px_rgba(13,148,136,.25)] backdrop-blur-xl dark:border-teal-950/60 dark:bg-slate-900/90 sm:px-6 lg:px-8">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-900 dark:text-white lg:text-base">@yield('title', 'KasMe')</p>
                <p class="hidden text-xs font-medium text-slate-400 sm:block">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <a href="{{ route('transactions.create') }}" class="btn-primary hidden sm:inline-flex">
                    <x-icon name="plus" size="4"/>
                    <span>Transaksi</span>
                </a>
                <button type="button" @click="mobileAccountMenu = true" class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-xs font-bold text-white shadow-sm ring-1 ring-white/30 hover:scale-105 active:scale-95 transition focus-visible:ring-2 focus-visible:ring-emerald-500 md:hidden" aria-label="Buka menu akun" :aria-expanded="mobileAccountMenu" aria-controls="mobile-more-sheet">{{ $initials }}</button>
                <div class="relative hidden md:block" @click.outside="userMenu = false">
                    <button type="button" @click="userMenu = !userMenu" class="flex h-10 cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200/80 bg-white/80 px-2.5 pr-3 shadow-2xs hover:bg-slate-50 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/80 dark:hover:bg-slate-800/90" aria-label="Buka menu akun {{ $user->name }}" :aria-expanded="userMenu" aria-controls="desktop-user-menu">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-emerald-600 to-teal-700 text-xs font-bold text-white shadow-xs">{{ $initials }}</span>
                        <span class="max-w-32 truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $user->name }}</span>
                        <x-icon name="chevron" size="4" class="text-slate-400 transition-transform" ::class="userMenu ? '-rotate-90' : 'rotate-90'"/>
                    </button>
                    <div id="desktop-user-menu" x-cloak x-show="userMenu" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-1 opacity-0 scale-95" x-transition:enter-end="translate-y-0 opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="translate-y-0 opacity-100 scale-100" x-transition:leave-end="translate-y-1 opacity-0 scale-95" class="premium-surface absolute right-0 z-50 mt-2 w-64 origin-top-right overflow-hidden shadow-xl shadow-teal-950/10" role="menu">
                        <div class="flex items-center gap-3 border-b border-slate-100 bg-teal-50/30 px-4 py-3.5 dark:border-slate-800 dark:bg-teal-950/20">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-xs font-bold text-white shadow-sm">{{ $initials }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="p-1.5 space-y-0.5">
                            <a href="{{ route('profile.edit') }}" @click="userMenu = false" class="flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-teal-50/60 hover:text-teal-900 dark:text-slate-200 dark:hover:bg-slate-800" role="menuitem">
                                <span class="grid size-7 place-items-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><x-icon name="user" size="4" /></span>
                                <span>Profil</span>
                            </a>
                            <a href="{{ route('settings.edit') }}" @click="userMenu = false" class="flex min-h-10 items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-teal-50/60 hover:text-teal-900 dark:text-slate-200 dark:hover:bg-slate-800" role="menuitem">
                                <span class="grid size-7 place-items-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><x-icon name="settings" size="4" /></span>
                                <span>Pengaturan</span>
                            </a>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 p-1.5 dark:border-slate-800">
                            @csrf
                            <button type="submit" class="flex min-h-10 w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40" role="menuitem">
                                <span class="grid size-7 place-items-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"><x-icon name="logout" size="4" /></span>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <main id="main-content" class="mobile-safe p-4 sm:p-6 lg:p-8">
            @include('components.flash-message')
            @yield('content')
        </main>
    </div>

    <!-- Mobile FAB -->
    <a href="{{ route('transactions.create') }}" class="fixed bottom-[calc(4.75rem+env(safe-area-inset-bottom))] right-4 z-30 grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 text-white shadow-lg shadow-teal-900/35 ring-2 ring-white/60 transition hover:scale-105 active:scale-95 dark:ring-teal-400/20 sm:hidden" aria-label="Tambah transaksi">
        <x-icon name="plus" size="6"/>
    </a>

    <!-- Mobile Bottom Navigation -->
    <nav class="bottom-safe fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-teal-100/80 bg-white/95 px-1 pt-1.5 shadow-[0_-4px_24px_-16px_rgba(13,148,136,.25)] backdrop-blur-xl dark:border-teal-950/60 dark:bg-slate-900/95 lg:hidden" aria-label="Navigasi bawah">
        @foreach([['dashboard','dashboard','Dasbor'],['transaction','transactions.*','Transaksi'],['wallet','accounts.*','Akun'],['report','reports.*','Laporan']] as [$icon,$pattern,$label])
        <a href="{{ route($routeNames[$icon]) }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[10px] font-medium transition duration-150 {{ request()->routeIs($pattern) ? 'bg-gradient-to-b from-emerald-50 to-teal-50 font-bold text-emerald-700 dark:from-emerald-950/70 dark:to-teal-950/40 dark:text-emerald-300' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
            <x-icon :name="$icon" size="5" class="{{ request()->routeIs($pattern) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}"/>
            <span>{{ $label }}</span>
        </a>
        @endforeach
        <button type="button" @click="moreSheet = true" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[10px] font-medium text-slate-500 transition hover:bg-slate-50 dark:hover:bg-slate-800/60" aria-label="Buka menu lainnya" :aria-expanded="moreSheet" aria-controls="mobile-more-sheet">
            <x-icon name="more" size="5" class="text-slate-400"/>
            <span>Lainnya</span>
        </button>
    </nav>

    <!-- Mobile More Bottom Sheet -->
    <div id="mobile-more-sheet" x-cloak x-show="moreSheet || mobileAccountMenu" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-labelledby="mobile-menu-title">
        <div x-show="moreSheet || mobileAccountMenu" x-transition.opacity class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="moreSheet = false; mobileAccountMenu = false"></div>
        <section x-show="moreSheet || mobileAccountMenu" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full" class="bottom-safe absolute inset-x-0 bottom-0 max-h-[85dvh] overflow-y-auto rounded-t-3xl border-t border-teal-100 bg-gradient-to-b from-white via-teal-50/30 to-white p-5 shadow-2xl dark:border-teal-950 dark:bg-gradient-to-b dark:from-slate-900 dark:via-teal-950/30 dark:to-slate-900">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-slate-300 dark:bg-slate-700"></div>
            <div class="mb-4 flex items-center justify-between">
                <h2 id="mobile-menu-title" class="text-lg font-bold text-slate-900 dark:text-white" x-text="mobileAccountMenu ? 'Menu akun' : 'Menu lainnya'">Menu lainnya</h2>
                <button type="button" @click="moreSheet = false; mobileAccountMenu = false" class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Tutup menu"><x-icon name="close" size="5"/></button>
            </div>
            <div class="mb-4 flex items-center gap-3 rounded-2xl border border-teal-100/90 bg-white/90 p-3.5 shadow-xs dark:border-teal-950 dark:bg-slate-900/90">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 text-xs font-bold text-white shadow-xs">{{ $initials }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                @foreach([
                    ['category', 'Kategori', 'icon-badge-amber'],
                    ['transfer', 'Transfer', 'icon-badge-cyan'],
                    ['budget', 'Anggaran', 'icon-badge-violet'],
                    ['bill', 'Tagihan', 'icon-badge-rose'],
                    ['debt', 'Utang & Piutang', 'icon-badge-blue'],
                    ['goal', 'Target Tabungan', 'icon-badge-emerald'],
                    ['settings', 'Pengaturan', 'icon-badge-slate'],
                    ['user', 'Profil', 'icon-badge-emerald'],
                ] as [$icon, $label, $badgeClass])
                <a href="{{ route($routeNames[$icon]) }}" @click="moreSheet = false; mobileAccountMenu = false" class="flex min-h-14 min-w-0 items-center gap-3 rounded-xl border border-slate-200/80 bg-white/95 p-3 text-sm font-semibold text-slate-700 shadow-xs transition hover:border-teal-200 hover:bg-teal-50/50 dark:border-slate-800 dark:bg-slate-800/90 dark:text-slate-200 dark:hover:border-teal-900 dark:hover:bg-teal-950/30">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $badgeClass }}">
                        <x-icon :name="$icon" size="5"/>
                    </span>
                    <span class="min-w-0 leading-snug">{{ $label }}</span>
                </a>
                @endforeach
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-slate-200/80 pt-3 dark:border-slate-800">
                @csrf
                <button type="submit" class="flex min-h-12 w-full items-center justify-center gap-3 rounded-xl bg-rose-50 text-sm font-semibold text-rose-600 transition hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-400 dark:hover:bg-rose-950">
                    <x-icon name="logout" size="5"/>
                    <span>Keluar</span>
                </button>
            </form>
        </section>
    </div>

    <!-- Confirmation Modal -->
    <div x-cloak x-show="confirmOpen" class="fixed inset-0 z-[60] grid place-items-end p-4 sm:place-items-center" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div x-show="confirmOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs" @click="confirmOpen = false"></div>
        <section x-show="confirmOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0" class="relative w-full max-w-md rounded-3xl border border-slate-200/80 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="grid h-12 w-12 place-items-center rounded-2xl"
                 :class="confirmButton === 'Nonaktifkan' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/80 dark:text-amber-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/80 dark:text-rose-400'">
                <template x-if="confirmButton === 'Nonaktifkan'">
                    <x-icon name="clock" size="6" />
                </template>
                <template x-if="confirmButton !== 'Nonaktifkan'">
                    <x-icon name="trash" size="6" />
                </template>
            </div>
            <h2 id="confirm-title" class="mt-4 text-xl font-bold tracking-tight text-slate-900 dark:text-white" x-text="confirmTitle">Konfirmasi tindakan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="confirmMessage"></p>
            <div class="mt-6 flex justify-end gap-2.5">
                <button type="button" @click="confirmOpen = false" class="btn-secondary">Batal</button>
                <button type="button" @click="confirmForm.dataset.confirmed = 'true'; confirmForm.requestSubmit(); confirmOpen = false"
                        :class="confirmButton === 'Nonaktifkan' ? 'btn-warning !bg-amber-600 hover:!bg-amber-700 !text-white' : 'btn-danger !bg-rose-600 hover:!bg-rose-700 !text-white'"
                        class="shadow-sm font-semibold"
                        x-text="confirmButton">Lanjutkan</button>
            </div>
        </section>
    </div>
</div>
</body>
</html>
