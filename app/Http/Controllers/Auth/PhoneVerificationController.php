<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\VerificationCode;
use App\Services\TwilioSmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PhoneVerificationController extends Controller
{
    private const PHONE_VERIFICATION_SESSION_KEY = 'auth_phone_verification';

    public function __construct(
        private TwilioSmsService $sms
    ) {
        $this->middleware('auth');
    }

    public function show(Request $request): View
    {
        $phoneVerification = $this->phoneVerificationState($request);

        return view('auth.phone-verify', [
            'phone' => (string) old('phone', $phoneVerification['phone'] !== '' ? $phoneVerification['phone'] : ((string) ($request->user()->phone ?? ''))),
            'phoneVerification' => $phoneVerification,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        if (! $user) {
            return redirect()->route('register')->withErrors(['phone' => 'Phone number is required.']);
        }

        $phone = $this->normalizePhoneToE164((string) $request->input('phone'));
        if ($phone === null) {
            return back()->withErrors(['phone' => 'Phone number must be in E.164 format, e.g. +12345678900.']);
        }

        $latest = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', 'sms')
            ->where('phone_number', $phone)
            ->orderByDesc('id')
            ->first();

        if ($latest && $latest->last_sent_at && $latest->last_sent_at->gt(now()->subSeconds(60))) {
            return back()->withErrors(['otp' => 'Please wait a moment before requesting another code.']);
        }

        VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', 'sms')
            ->where('status', 'pending')
            ->update(['status' => 'failed']);

        $code = (string) random_int(100000, 999999);

        VerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '',
            'code_hash' => Hash::make($code),
            'type' => 'sms',
            'phone_number' => $phone,
            'email' => null,
            'status' => 'pending',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        try {
            $this->sms->send($phone, "Your GASQ verification code is {$code}. It expires in 10 minutes.");
        } catch (\Throwable $e) {
            Log::error('Auth phone verification OTP send failed', [
                'user_id' => $user?->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['phone' => 'We could not send a verification code right now. Please try again in a moment.'])
                ->withInput();
        }

        $this->storePhoneVerificationState($request, $phone, false);

        return back()->with('status', 'Verification code sent.');
    }

    public function check(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $v = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'min:4', 'max:10'],
        ]);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $phone = $this->normalizePhoneToE164((string) $request->input('phone'));
        if ($phone === null) {
            return back()->withErrors(['phone' => 'Invalid phone number format.']);
        }

        $row = VerificationCode::query()
            ->where('user_id', $user->id)
            ->where('type', 'sms')
            ->where('phone_number', $phone)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return back()->withErrors(['otp' => 'No active code found. Please request a new code.']);
        }

        if (Carbon::parse($row->expires_at)->isPast()) {
            $row->status = 'failed';
            $row->save();
            return back()->withErrors(['otp' => 'Code expired. Please request a new code.']);
        }

        if (($row->attempts ?? 0) >= 5) {
            $row->status = 'failed';
            $row->save();
            return back()->withErrors(['otp' => 'Too many attempts. Please request a new code.']);
        }

        $code = (string) $request->input('code');
        $ok = is_string($row->code_hash) && $row->code_hash !== '' && Hash::check($code, $row->code_hash);
        if (! $ok) {
            $row->attempts = (int) ($row->attempts ?? 0) + 1;
            $row->save();
            return back()->withErrors(['otp' => 'Invalid code. Please try again.']);
        }

        $row->status = 'verified';
        $row->verified_at = now();
        $row->save();

        $user->phone = $phone;
        $user->phone_verified = true;
        $user->save();
        $this->storePhoneVerificationState($request, $phone, true);

        return redirect()->intended('/home')->with('status', 'Phone verified.');
    }

    /**
     * @return array{phone: string, verified: bool}
     */
    private function phoneVerificationState(Request $request): array
    {
        $state = $request->session()->get(self::PHONE_VERIFICATION_SESSION_KEY, []);

        if (! is_array($state)) {
            return ['phone' => '', 'verified' => false];
        }

        return [
            'phone' => (string) ($state['phone'] ?? ''),
            'verified' => (bool) ($state['verified'] ?? false),
        ];
    }

    private function storePhoneVerificationState(Request $request, string $phone, bool $verified): void
    {
        $request->session()->put(self::PHONE_VERIFICATION_SESSION_KEY, [
            'phone' => $phone,
            'verified' => $verified,
        ]);
    }

    private function normalizePhoneToE164(string $phone): ?string
    {
        $p = preg_replace('/[\s\-\(\)]+/', '', trim($phone)) ?? '';
        if ($p === '') {
            return null;
        }
        if (! str_starts_with($p, '+')) {
            return null;
        }
        if (! preg_match('/^\\+[1-9]\\d{7,14}$/', $p)) {
            return null;
        }
        return $p;
    }
}
