@props([
    'icon' => 'wallet',
    'title',
    'description' => null,
    'action' => null,
    'actionLabel' => null,
    'accent' => 'cyan',
])
<section {{ $attributes->class([
    'empty-state transition duration-200',
    'border-cyan-200/90 bg-gradient-to-b from-cyan-50/50 via-white to-cyan-50/25 dark:border-cyan-900/60 dark:from-cyan-950/25 dark:via-slate-900/50 dark:to-cyan-950/15' => $accent === 'cyan',
    'border-emerald-200/90 bg-gradient-to-b from-emerald-50/50 via-white to-emerald-50/25 dark:border-emerald-900/60 dark:from-emerald-950/25 dark:via-slate-900/50 dark:to-emerald-950/15' => $accent === 'emerald',
    'border-rose-200/90 bg-gradient-to-b from-rose-50/50 via-white to-rose-50/25 dark:border-rose-900/60 dark:from-rose-950/25 dark:via-slate-900/50 dark:to-rose-950/15' => $accent === 'rose',
    'border-violet-200/90 bg-gradient-to-b from-violet-50/50 via-white to-violet-50/25 dark:border-violet-900/60 dark:from-violet-950/25 dark:via-slate-900/50 dark:to-violet-950/15' => $accent === 'violet',
    'border-amber-200/90 bg-gradient-to-b from-amber-50/50 via-white to-amber-50/25 dark:border-amber-900/60 dark:from-amber-950/25 dark:via-slate-900/50 dark:to-amber-950/15' => $accent === 'amber',
    'border-blue-200/90 bg-gradient-to-b from-blue-50/50 via-white to-blue-50/25 dark:border-blue-900/60 dark:from-blue-950/25 dark:via-slate-900/50 dark:to-blue-950/15' => $accent === 'blue',
]) }}>
    <span @class([
        'mx-auto grid size-14 place-items-center rounded-2xl border shadow-sm transition duration-200',
        'icon-badge-cyan' => $accent === 'cyan',
        'icon-badge-emerald' => $accent === 'emerald',
        'icon-badge-rose' => $accent === 'rose',
        'icon-badge-violet' => $accent === 'violet',
        'icon-badge-amber' => $accent === 'amber',
        'icon-badge-blue' => $accent === 'blue',
    ])><x-icon :name="$icon" size="7" /></span>
    <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
    @if($description)<p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $description }}</p>@endif
    @if($action && $actionLabel)
        <div class="mt-5">
            <a href="{{ $action }}" class="btn-primary">
                <x-icon name="plus" size="4" />
                <span>{{ $actionLabel }}</span>
            </a>
        </div>
    @endif
</section>
