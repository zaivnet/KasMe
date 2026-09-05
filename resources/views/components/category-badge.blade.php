@props(['category' => null, 'fallback' => 'Tanpa kategori', 'showType' => false])
@php
    $type = $category?->type;
    $fallbackColor = $type === 'income' ? '#059669' : '#e11d48';
    $color = $category?->color ?: $fallbackColor;
@endphp
<span {{ $attributes->class(['inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white/90 px-2 py-0.5 text-xs font-medium text-slate-700 shadow-xs dark:border-slate-800 dark:bg-slate-900/90 dark:text-slate-200']) }}>
    <span class="grid size-5 shrink-0 place-items-center rounded-md text-white shadow-xs" style="background-color: {{ $color }}">
        <x-icon :name="$category?->icon ?: 'category'" size="3" />
    </span>
    <span class="min-w-0 truncate font-medium">{{ $category?->name ?? $fallback }}</span>
    @if($showType && $category)<span class="sr-only">{{ App\Models\Category::TYPES[$type] }}</span>@endif
</span>
