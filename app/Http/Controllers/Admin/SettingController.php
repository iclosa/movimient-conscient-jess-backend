<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $values = collect(Setting::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => Setting::get($key)])->all();

        return view('admin.settings.edit', ['values' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'center_name' => ['required', 'max:120'],
            'center_address' => ['nullable', 'max:200'],
            'center_phone' => ['nullable', 'max:40'],
            'center_email' => ['nullable', 'email'],
            'center_instagram' => ['nullable', 'max:120'],
            'center_website' => ['nullable', 'url'],
            'center_latitude' => ['required', 'numeric', 'between:-90,90'],
            'center_longitude' => ['required', 'numeric', 'between:-180,180'],
            'center_timezone' => ['required', 'timezone:all'],
            'cancellation_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'booking_open_days' => ['required', 'integer', 'min:1', 'max:90'],
            'default_capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'welcome_message' => ['nullable', 'max:200'],
            'terms_url' => ['nullable', 'url'],
        ], [], [
            'center_name' => 'nom del centre',
            'center_email' => 'correu',
            'center_latitude' => 'latitud',
            'center_longitude' => 'longitud',
            'center_timezone' => 'zona horària',
            'center_website' => 'web',
            'cancellation_hours' => 'hores de cancel·lació',
            'booking_open_days' => 'dies d\'antelació',
            'default_capacity' => 'places per defecte',
            'terms_url' => 'enllaç de condicions',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value === null ? '' : (string) $value);
        }

        Setting::set('waitlist_enabled', $request->boolean('waitlist_enabled') ? '1' : '0');

        return back()->with('status', 'Ajustos desats.');
    }
}
