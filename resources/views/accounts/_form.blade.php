<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="text-sm font-medium">Nama akun</label>
        <input id="name" name="name" value="{{ old('name', $account->name ?? '') }}" required maxlength="100" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
        <x-form-error name="name" />
    </div>
    <div>
        <label for="type" class="text-sm font-medium">Jenis</label>
        <select id="type" name="type" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $account->type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-form-error name="type" />
    </div>
    <div>
        <label for="currency" class="text-sm font-medium">Mata uang</label>
        <input id="currency" name="currency" value="{{ old('currency', $account->currency ?? $preferences->currency) }}" required maxlength="10" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 uppercase dark:border-slate-700 dark:bg-slate-950">
        <x-form-error name="currency" />
    </div>
    @if (! isset($account))
        <div class="sm:col-span-2">
            <label for="opening_balance" class="text-sm font-medium">Saldo awal</label>
            <input id="opening_balance" name="opening_balance" type="number" inputmode="decimal" value="{{ old('opening_balance', '0.00') }}" required step="0.01" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-xl font-semibold tabular-nums dark:border-slate-700 dark:bg-slate-950">
            <p class="mt-1 text-sm text-slate-500">Nilai ini tidak dapat diedit langsung setelah akun dibuat.</p>
            <x-form-error name="opening_balance" />
        </div>
    @endif
    <x-icon-picker name="icon" :value="$account->icon ?? ''" label="Ikon akun" variant="account" class="sm:col-span-2" />
    <x-color-picker name="color" :value="$account->color ?? '#047857'" label="Warna akun" />
    <label class="flex items-center gap-3 text-sm sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $account->is_active ?? true)) class="rounded border-slate-300">
        Akun aktif
    </label>
</div>
