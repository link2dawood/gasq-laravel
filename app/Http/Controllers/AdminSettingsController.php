<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public const LOGO_SETTING_KEY = 'site_logo';

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        $logoPath = Setting::get(self::LOGO_SETTING_KEY);

        return view('admin.settings', [
            'settingsByGroup' => $settings,
            'siteLogoPath' => $logoPath,
            'twilioDebug' => app(\App\Services\TwilioSmsService::class)->debugContext(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['array'],
            'settings.*.value' => ['nullable', 'string'],
            'settings.*.group' => ['nullable', 'string'],
        ]);

        foreach ($data['settings'] ?? [] as $key => $payload) {
            $value = $payload['value'] ?? null;
            $group = $payload['group'] ?? 'general';
            Setting::set($key, $value, $group);
        }

        return redirect()->route('admin.settings')->with('success', 'Settings updated.');
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
        ]);

        $dir = public_path('images');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ext = $request->file('logo')->getClientOriginalExtension();
        $filename = 'site-logo.' . $ext;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        foreach (File::glob($dir . DIRECTORY_SEPARATOR . 'site-logo.*') ?: [] as $old) {
            File::delete($old);
        }

        $request->file('logo')->move($dir, $filename);

        Setting::set(self::LOGO_SETTING_KEY, 'images/' . $filename, 'branding');

        return redirect()->route('admin.settings')->with('success', 'Website logo updated.');
    }

    public function removeLogo(): RedirectResponse
    {
        $path = Setting::get(self::LOGO_SETTING_KEY);
        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
        Setting::set(self::LOGO_SETTING_KEY, '', 'branding');

        return redirect()->route('admin.settings')->with('success', 'Logo removed. Default logo will show.');
    }
}
