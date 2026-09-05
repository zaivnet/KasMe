@props(['name' => 'icon', 'value' => '', 'label' => 'Ikon', 'variant' => 'category'])
@php
    $accountIcons = [
        'wallet' => 'Dompet',
        'bank' => 'Bank',
        'card' => 'Kartu',
        'cash' => 'Tunai',
        'savings' => 'Tabungan',
        'ewallet' => 'E-Wallet',
        'investment' => 'Investasi',
        'other' => 'Lainnya',
    ];
    $categoryIcons = [
        'wallet' => 'Dompet',
        'bank' => 'Bank',
        'card' => 'Kartu',
        'cash' => 'Tunai',
        'savings' => 'Tabungan',
        'category' => 'Kategori',
        'bill' => 'Tagihan',
        'goal' => 'Target',
    ];
    $icons = $variant === 'account' ? $accountIcons : $categoryIcons;
    $pickerId = 'icon-picker-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $name).'-'.$variant;
@endphp
<fieldset {{ $attributes }} x-data="{ selected: @js(old($name, $value)) }" data-icon-picker="{{ $variant }}">
    <legend id="{{ $pickerId }}-label" class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }} <span class="font-normal text-slate-400">(opsional)</span></legend>
    <input type="hidden" name="{{ $name }}" :value="selected">
    <div class="mt-2 grid grid-cols-[repeat(auto-fit,minmax(5rem,1fr))] gap-2 rounded-2xl border border-teal-100/80 bg-teal-50/30 p-2.5 dark:border-slate-800 dark:bg-slate-950/50" role="radiogroup" aria-labelledby="{{ $pickerId }}-label">
        @foreach($icons as $icon => $iconLabel)
        <button
            type="button"
            role="radio"
            data-icon-value="{{ $icon }}"
            @click="selected = selected === '{{ $icon }}' ? '' : '{{ $icon }}'"
            :aria-checked="selected === '{{ $icon }}'"
            :class="selected === '{{ $icon }}' ? 'border-emerald-500 bg-gradient-to-b from-emerald-50 to-teal-50 text-emerald-800 shadow-md shadow-emerald-600/10 ring-2 ring-emerald-500/30 dark:border-emerald-500 dark:from-emerald-950/80 dark:to-teal-950/60 dark:text-emerald-300' : 'border-slate-200/90 bg-white/95 text-slate-600 hover:-translate-y-0.5 hover:border-teal-300 hover:bg-teal-50/40 hover:text-teal-900 hover:shadow-xs dark:border-slate-700/80 dark:bg-slate-900/90 dark:text-slate-400 dark:hover:border-teal-800 dark:hover:bg-teal-950/30 dark:hover:text-slate-100'"
            class="flex min-h-[72px] min-w-0 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border px-2 py-2.5 transition duration-150"
            title="{{ $iconLabel }}"
            aria-label="Pilih ikon {{ $iconLabel }}"
        ><x-icon :name="$icon" size="7" class="shrink-0"/><span class="text-xs font-semibold leading-none">{{ $iconLabel }}</span></button>
        @endforeach
    </div>
    <p x-show="selected && !@js(array_keys($icons)).includes(selected)" class="mt-2 text-xs text-slate-500">Ikon lama “<span x-text="selected"></span>” tetap dipertahankan sampai Anda memilih ikon baru.</p>
    <x-form-error :name="$name" />
</fieldset>
