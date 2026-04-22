<?php

namespace App\Http\Controllers;

use App\Services\TwilioSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminTwilioController extends Controller
{
    public function __construct(
        private TwilioSmsService $sms
    ) {
        $this->middleware(['auth', 'admin']);
    }

    public function show(): View
    {
        return view('admin.twilio-health', [
            'twilioHealth' => $this->sms->healthCheck(),
            'twilioDebug' => $this->sms->debugContext(),
        ]);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = $this->normalizePhoneToE164((string) $data['phone']);
        if ($phone === null) {
            return back()->withErrors([
                'phone' => 'Phone number must be in E.164 format, e.g. +12345678900.',
            ])->withInput();
        }

        $body = trim((string) ($data['body'] ?? '')) ?: 'GASQ Twilio health check message.';

        try {
            $this->sms->send($phone, $body);
        } catch (\Throwable $e) {
            Log::error('Admin Twilio health test send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'twilio' => $this->sms->debugContext(),
            ]);

            return back()
                ->withErrors(['phone' => $this->sms->userFacingError($e)])
                ->withInput()
                ->with('twilio_test_result', [
                    'ok' => false,
                    'message' => 'Test SMS failed.',
                ]);
        }

        return redirect()
            ->route('admin.twilio.show')
            ->with('success', 'Test SMS sent successfully.')
            ->with('twilio_test_result', [
                'ok' => true,
                'message' => 'Test SMS sent successfully.',
                'phone' => $this->maskPhone($phone),
            ]);
    }

    private function normalizePhoneToE164(string $phone): ?string
    {
        $p = preg_replace('/[\s\-\(\)]+/', '', trim($phone)) ?? '';
        if ($p === '' || ! str_starts_with($p, '+')) {
            return null;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $p) ? $p : null;
    }

    private function maskPhone(string $value): string
    {
        return preg_replace('/\d(?=\d{4})/', '*', $value) ?? $value;
    }
}
