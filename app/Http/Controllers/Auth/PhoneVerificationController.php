<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\PhoneOtpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PhoneVerificationController extends Controller
{
    private const PHONE_VERIFICATION_SESSION_KEY = 'auth_phone_verification';

    public function __construct(
        private PhoneOtpService $phoneOtp
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

    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phone number is required.'], 422);
            }

            return redirect()->route('register')->withErrors(['phone' => 'Phone number is required.']);
        }

        $result = $this->phoneOtp->sendOtp($user, 'sms', (string) $request->input('phone'));
        if (! $result['ok']) {
            $field = $result['field'] ?? 'phone';

            Log::error('Auth phone verification OTP send failed', [
                'user_id' => $user?->id,
                'phone' => $result['phone'] ?? (string) $request->input('phone'),
                'error' => $result['message'] ?? 'OTP send failed',
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $result['message'] ?? 'We could not send a verification code right now. Please try again in a moment.',
                    'field' => $field,
                    'phone' => $result['phone'] ?? (string) $request->input('phone'),
                ], 422);
            }

            return back()
                ->withErrors([$field => $result['message'] ?? 'We could not send a verification code right now. Please try again in a moment.'])
                ->withInput();
        }

        $phone = (string) ($result['phone'] ?? '');

        $this->storePhoneVerificationState($request, $phone, false);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Verification code sent.',
                'phone' => $phone,
                'verified' => false,
            ]);
        }

        return back()->with('status', 'Verification code sent.');
    }

    public function check(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $v = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'min:4', 'max:10'],
        ]);
        if ($v->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid verification request.',
                    'errors' => $v->errors(),
                ], 422);
            }

            return back()->withErrors($v)->withInput();
        }

        $result = $this->phoneOtp->verifyOtp($user, 'sms', (string) $request->input('phone'), (string) $request->input('code'));
        if (! $result['ok']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $result['message'] ?? 'Invalid verification code.',
                    'field' => $result['field'] ?? 'otp',
                ], 422);
            }

            return back()->withErrors([
                $result['field'] ?? 'otp' => $result['message'] ?? 'Invalid verification code.',
            ])->withInput();
        }

        $phone = (string) ($result['phone'] ?? '');

        $user->phone = $phone;
        $user->phone_verified = true;
        $user->save();
        $this->storePhoneVerificationState($request, $phone, true);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Phone verified.',
                'phone' => $phone,
                'verified' => true,
            ]);
        }

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
}
