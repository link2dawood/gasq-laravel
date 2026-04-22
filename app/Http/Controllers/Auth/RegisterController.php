<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneOtpService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    private const PHONE_VERIFICATION_SESSION_KEY = 'auth_phone_verification';

    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    public function __construct(
        private PhoneOtpService $phoneOtp
    ) {
        $this->middleware('guest');
    }

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected function registered(Request $request, $user)
    {
        // Enforce phone verification for signup.
        if (! $user || ! is_string($user->phone) || trim($user->phone) === '') {
            return redirect()->route('home');
        }

        $phone = $this->phoneOtp->normalizePhoneToE164((string) $user->phone);

        // Require E.164 format for Twilio.
        if ($phone === null) {
            return redirect()
                ->route('phone.verify.show')
                ->with(self::PHONE_VERIFICATION_SESSION_KEY, [
                    'phone' => (string) $user->phone,
                    'verified' => false,
                ])
                ->withErrors(['phone' => 'Phone number must be in E.164 format, e.g. +12345678900.']);
        }

        $request->session()->put(self::PHONE_VERIFICATION_SESSION_KEY, [
            'phone' => $phone,
            'verified' => false,
        ]);

        $result = $this->phoneOtp->sendOtp($user, 'sms', $phone);

        if (! $result['ok']) {
            Log::error('Twilio OTP send failed', [
                'user_id' => $user?->id,
                'phone' => $phone,
                'error' => $result['message'] ?? 'OTP send failed',
            ]);

            return redirect()
                ->route('phone.verify.show')
                ->withErrors(['phone' => $result['message'] ?? 'We could not send a verification code right now. Please try again in a moment.']);
        }

        return redirect()->route('phone.verify.show')->with('status', 'Verification code sent.');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'user_type' => ['required', 'in:buyer,vendor'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'user_type' => $data['user_type'] ?? 'buyer',
            'company' => $data['company'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
    }
}
