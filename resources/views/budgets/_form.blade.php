<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="category_id" class="text-sm font-medium">Kategori pengeluaran</label>
        <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
            <option value="">Pilih kategori</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $budget->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>@endforeach
        </select>
        <x-form-error name="category_id" />
        @if($categories->isEmpty())<p class="mt-2 text-sm text-amber-700 dark:text-amber-400">Buat kategori pengeluaran aktif sebelum menambahkan anggaran.</p>@endif
    </div>
    <div>
        <label for="amount" class="text-sm font-medium">Jumlah anggaran</label>
        <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $budget->amount ?? '') }}" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 tabular-nums dark:border-slate-700 dark:bg-slate-950">
        <x-form-error name="amount" />
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div><label for="month" class="text-sm font-medium">Bulan</label><select id="month" name="month" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">@foreach(range(1,12) as $value)<option value="{{ $value }}" @selected((int) old('month', $budget->month ?? $defaultMonth ?? now()->month) === $value)>{{ \Carbon\CarbonImmutable::create(2000, $value)->locale('id')->translatedFormat('F') }}</option>@endforeach</select><x-form-error name="month" /></div>
        <div><label for="year" class="text-sm font-medium">Tahun</label><input id="year" name="year" type="number" min="2000" max="9999" value="{{ old('year', $budget->year ?? $defaultYear ?? now()->year) }}" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"><x-form-error name="year" /></div>
    </div>
</div>
