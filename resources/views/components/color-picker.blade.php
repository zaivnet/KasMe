@props(['name' => 'color', 'value' => '#047857', 'label' => 'Warna'])
@php($colors = ['#047857','#0f766e','#0369a1','#1d4ed8','#7e22ce','#be123c','#c2410c','#475569'])
<fieldset {{ $attributes }} x-data="{ selected: @js(old($name, $value ?: '#047857')) }">
    <legend class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }} <span class="font-normal text-slate-400">(opsional)</span></legend>
    <div class="mt-2 flex flex-wrap items-center gap-2.5 rounded-2xl border border-teal-100/80 bg-teal-50/30 p-3 dark:border-slate-800 dark:bg-slate-950/50">
        @foreach($colors as $color)
            <button type="button" @click="selected = '{{ $color }}'" class="relative grid h-11 w-11 place-items-center rounded-xl border-2 border-white shadow-sm ring-1 ring-slate-200/90 transition hover:-translate-y-0.5 hover:scale-105 hover:shadow-md dark:border-slate-900 dark:ring-slate-700" :class="selected.toLowerCase() === '{{ $color }}' ? 'ring-2 ring-offset-2 ring-emerald-500 shadow-emerald-500/20 dark:ring-emerald-400 dark:ring-offset-slate-900' : ''" style="background-color: {{ $color }}" aria-label="Pilih warna {{ $color }}" :aria-pressed="selected.toLowerCase() === '{{ $color }}'">
                <span x-show="selected.toLowerCase() === '{{ $color }}'" class="text-xs font-bold text-white drop-shadow-xs">✓</span>
            </button>
        @endforeach
        <label class="relative grid h-11 w-11 cursor-pointer place-items-center overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-white/90 text-sm font-bold text-slate-600 transition hover:-translate-y-0.5 hover:border-emerald-500 hover:text-emerald-700 hover:shadow-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400" title="Warna khusus">
            <span>+</span>
            <input name="{{ $name }}" type="color" x-model="selected" class="absolute inset-0 cursor-pointer opacity-0" aria-label="Pilih warna khusus">
        </label>
        <span class="ml-1 text-xs font-semibold uppercase tracking-wider text-slate-500 tabular-nums" x-text="selected"></span>
    </div>
    <x-form-error :name="$name" />
</fieldset>
