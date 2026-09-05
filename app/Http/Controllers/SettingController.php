<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'dateFormats' => Setting::DATE_FORMATS,
            'themes' => Setting::THEMES,
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $request->user()->setting()->updateOrCreate([], $request->validated());

        return redirect()->route('settings.edit')->with('success', 'Preferensi berhasil diperbarui.');
    }
}
