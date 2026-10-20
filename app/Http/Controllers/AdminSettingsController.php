<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ProcessEmailCopies;
use App\Services\TwilioSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $copyKeys = array_merge([ProcessEmailCopies::ADDRESS_KEY], array_column(ProcessEmailCopies::PROCESSES, 'key'));
        $settings = Setting::whereNotIn('key', $copyKeys)->orderBy('group')->orderBy('key')->get()->groupBy('group');
        $logoPath = Setting::get(self::LOGO_SETTING_KEY);

        return view('admin.settings', [
            'settingsByGroup' => $settings,
            'siteLogoPath' => $logoPath,
            'twilioDebug' => app(TwilioSmsService::class)->debugContext(),
            'emailCopies' => app(ProcessEmailCopies::class)->settings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['array'],
            'settings.*.value' => ['nullable', 'string'],
            'settings.*.group' => ['nullable', 'string'],
        ]);

        // These fields have their own validated process settings form.
        $copyKeys = array_merge([ProcessEmailCopies::ADDRESS_KEY], array_column(ProcessEmailCopies::PROCESSES, 'key'));
        foreach ($copyKeys as $key) {
            abort_if(array_key_exists($key, $data['settings'] ?? []), 422, 'Use the process email copy settings form.');
        }

        foreach ($data['settings'] ?? [] as $key => $payload) {
            $value = $payload['value'] ?? null;
            $group = $payload['group'] ?? 'general';
            Setting::set($key, $value, $group);
        }

        return redirect()->route('admin.settings')->with('success', 'Settings updated.');
    }

    public function updateEmailCopies(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bcc_address' => ['required', 'email:rfc', 'max:254'],
            'vendor_weighted' => ['required', 'boolean'],
            'estimates' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($data) {
            Setting::set(ProcessEmailCopies::ADDRESS_KEY, trim($data['bcc_address']), 'email_copies');
            foreach (ProcessEmailCopies::PROCESSES as $process => $definition) {
                Setting::set($definition['key'], $data[$process] ? '1' : '0', 'email_copies');
            }
        });
        foreach (array_merge([ProcessEmailCopies::ADDRESS_KEY], array_column(ProcessEmailCopies::PROCESSES, 'key')) as $key) {
            Cache::forget('setting.'.$key);
        }

        return redirect()->route('admin.settings')->with('success', 'Process email BCC settings updated.');
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
        $filename = 'site-logo.'.$ext;
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        foreach (File::glob($dir.DIRECTORY_SEPARATOR.'site-logo.*') ?: [] as $old) {
            File::delete($old);
        }

        $request->file('logo')->move($dir, $filename);

        Setting::set(self::LOGO_SETTING_KEY, 'images/'.$filename, 'branding');

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
