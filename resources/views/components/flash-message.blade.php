@php
    $toast = session('success') ? ['success', session('success')] : (session('status') ? ['info', session('status')] : (session('warning') ? ['warning', session('warning')] : ($errors->any() ? ['error', 'Periksa kembali kolom yang ditandai.'] : null)));
@endphp
@if($toast)
<div x-data="{ show: true, timer: null, init() { this.timer = setTimeout(() => this.show = false, 5000); } }"
     @mouseenter="clearTimeout(timer)"
     @mouseleave="timer = setTimeout(() => show = false, 3000)"
     x-show="show"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-x-4 top-4 z-50 flex items-start gap-3 rounded-2xl border bg-white/95 p-4 shadow-xl backdrop-blur-md dark:bg-slate-900/95 sm:inset-x-auto sm:right-6 sm:top-6 sm:w-96 {{ $toast[0] === 'error' ? 'border-rose-200 shadow-rose-950/5 dark:border-rose-900/70' : ($toast[0] === 'success' ? 'border-emerald-200 shadow-emerald-950/5 dark:border-emerald-900/70' : ($toast[0] === 'warning' ? 'border-amber-200 shadow-amber-950/5 dark:border-amber-900/70' : 'border-cyan-200 shadow-cyan-950/5 dark:border-cyan-900/70')) }}"
     role="{{ $toast[0] === 'error' ? 'alert' : 'status' }}">
    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $toast[0] === 'error' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/80 dark:text-rose-400' : ($toast[0] === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-400' : ($toast[0] === 'warning' ? 'bg-amber-50 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300' : 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/80 dark:text-cyan-400')) }}">
        <x-icon :name="$toast[0] === 'error' ? 'close' : ($toast[0] === 'success' ? 'check' : ($toast[0] === 'warning' ? 'alert' : 'dashboard'))" size="5"/>
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $toast[0] === 'error' ? 'Perlu diperiksa' : ($toast[0] === 'success' ? 'Berhasil' : ($toast[0] === 'warning' ? 'Peringatan' : 'Informasi')) }}</p>
        <p class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $toast[1] }}</p>
    </div>
    <button type="button" @click="show = false" class="rounded-xl p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Tutup notifikasi">
        <x-icon name="close" size="4"/>
    </button>
</div>
@endif
