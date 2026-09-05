<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scheme-light dark:scheme-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#059669">
    <meta name="description" content="Masuk ke KasMe untuk mengelola keuangan pribadi Anda dengan aman dan rapi.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative grid min-h-screen place-items-center overflow-hidden bg-slate-50 p-4 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100 sm:p-6">
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-950/30"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-teal-300/30 blur-3xl dark:bg-teal-950/30"></div>
    <div class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-80 w-80 rounded-full bg-violet-300/20 blur-3xl dark:bg-violet-950/20"></div>

    <main class="relative w-full max-w-md">
        <a href="{{ url('/') }}" class="mb-7 flex items-center justify-center gap-3 text-center text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 text-sm font-bold text-white shadow-md shadow-teal-900/25 ring-2 ring-white/60">K</span>
            <span>KasMe</span>
        </a>
        <div class="relative rounded-3xl border border-teal-100/90 bg-white/95 p-6 shadow-xl shadow-teal-950/10 backdrop-blur-md dark:border-teal-950/70 dark:bg-slate-900/95 sm:p-8">
            @include('components.flash-message')
            @yield('content')
        </div>
    </main>
</body>
</html>
