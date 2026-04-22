<?php

namespace App\Http\Controllers;

use App\Services\PhoneOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\ImageManagerStatic as Image;

class ProfileController extends Controller
{
    private const PROFILE_PHONE_VERIFICATION_SESSION_KEY = 'profile_phone_verification';
    private const PROFILE_PHONE_VERIFICATION_TYPE = 'sms_profile_update';

    /**
     * Create a new controller instance.
     */
    public function __construct(
        private PhoneOtpService $phoneOtp
    )
    {
        $this->middleware('auth');
    }

    /**
     * Show the user's profile.
     */
    public function show()
    {
        return view('profile.show', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $phoneVerification = $this->profilePhoneVerificationState(request());

        return view('profile.edit', [
            'user' => $user,
            'phoneVerification' => $phoneVerification,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);

        $submittedPhone = trim((string) $request->input('phone', ''));
        $currentPhone = trim((string) ($user->phone ?? ''));
        $normalizedSubmittedPhone = $this->phoneOtp->normalizePhoneToE164($submittedPhone);
        $normalizedCurrentPhone = $this->phoneOtp->normalizePhoneToE164($currentPhone);

        if ($submittedPhone !== '') {
            if ($normalizedSubmittedPhone === null) {
                return back()->withErrors([
                    'phone' => 'Enter a valid phone number.',
                ])->withInput();
            }

            $samePhoneAsCurrent = $normalizedCurrentPhone !== null
                && $normalizedSubmittedPhone === $normalizedCurrentPhone;

            if (! $samePhoneAsCurrent) {
                $verification = $this->profilePhoneVerificationState($request);
                $verifiedPhone = (string) ($verification['phone'] ?? '');
                $isVerified = (bool) ($verification['verified'] ?? false);

                if (! $isVerified || $verifiedPhone !== $normalizedSubmittedPhone) {
                    return back()->withErrors([
                        'phone' => 'Verify this phone number before saving your profile.',
                    ])->withInput();
                }
            } elseif (! (bool) $user->phone_verified) {
                return back()->withErrors([
                    'phone' => 'Verify this phone number before saving your profile.',
                ])->withInput();
            }
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'company' => $request->company,
            'phone' => $submittedPhone !== '' ? $normalizedSubmittedPhone : null,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'phone_verified' => $submittedPhone === ''
                ? false
                : ($normalizedSubmittedPhone === $normalizedCurrentPhone ? (bool) $user->phone_verified : true),
        ]);

        $request->session()->forget(self::PROFILE_PHONE_VERIFICATION_SESSION_KEY);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }

    public function sendPhoneVerification(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $phone = $this->phoneOtp->normalizePhoneToE164((string) $request->input('phone'));
        if ($phone === null) {
            return back()->withErrors([
                'phone' => 'Enter a valid phone number.',
            ])->withInput();
        }

        if ((bool) $user?->phone_verified && $phone === $this->normalizePhoneToE164((string) ($user?->phone ?? ''))) {
            $this->storeProfilePhoneVerificationState($request, $phone, true);

            return back()->with('phone_status', 'This phone number is already verified.')->withInput();
        }

        $result = $this->phoneOtp->sendOtp($user, self::PROFILE_PHONE_VERIFICATION_TYPE, $phone);
        if (! $result['ok']) {
            $this->storeProfilePhoneVerificationState($request, $phone, false);

            Log::error('Profile phone verification OTP send failed', [
                'user_id' => $user?->id,
                'phone' => $result['phone'] ?? $phone,
                'error' => $result['message'] ?? 'OTP send failed',
            ]);

            return back()->withErrors([
                ($result['field'] ?? 'phone') === 'otp' ? 'phone_otp' : 'phone' => $result['message'] ?? 'We could not send a verification code right now. Please try again in a moment.',
            ])->withInput();
        }

        $this->storeProfilePhoneVerificationState($request, $phone, false);

        return back()->with('phone_status', 'Verification code sent to your phone.')->withInput();
    }

    public function verifyPhoneCode(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        $phone = $this->phoneOtp->normalizePhoneToE164((string) $request->input('phone'));
        if ($phone === null) {
            return back()->withErrors([
                'phone' => 'Enter a valid phone number.',
            ])->withInput();
        }

        $result = $this->phoneOtp->verifyOtp($user, self::PROFILE_PHONE_VERIFICATION_TYPE, $phone, (string) $request->input('code'));
        if (! $result['ok']) {
            $this->storeProfilePhoneVerificationState($request, $phone, false);

            return back()->withErrors([
                ($result['field'] ?? 'otp') === 'phone' ? 'phone' : 'phone_otp' => $result['message'] ?? 'Invalid verification code.',
            ])->withInput();
        }

        $this->storeProfilePhoneVerificationState($request, $phone, true);

        return back()->with('phone_status', 'Phone number verified. You can now save your profile.')->withInput();
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')->with('success', 'Password updated successfully.');
    }

    /**
     * Update the user's avatar with enhanced security.
     */
    public function updateAvatar(Request $request)
    {
        try {
            // Enhanced validation
            $request->validate([
                'avatar' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg,gif',
                    'max:2048',
                    'dimensions:min_width=50,min_height=50,max_width=2000,max_height=2000'
                ],
            ]);

            $user = Auth::user();
            $file = $request->file('avatar');

            // Additional security checks
            if (!$this->isSecureImageFile($file)) {
                Log::warning('Insecure file upload attempt', [
                    'user_id' => $user->id,
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType()
                ]);
                
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'File failed security validation'], 422);
                }
                return back()->withErrors(['avatar' => 'File failed security validation']);
            }

            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                Storage::disk('public')->delete('avatars/' . $user->avatar);
            }

            // Generate secure filename
            $avatarName = $user->id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file->getClientOriginalExtension();
            
            // Process and store image securely
            $this->processAndStoreImage($file, $avatarName);

            // Update user record
            $user->update(['avatar' => $avatarName]);

            // Log successful upload
            Log::info('Avatar uploaded successfully', [
                'user_id' => $user->id,
                'filename' => $avatarName
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => 'Avatar updated successfully']);
            }
            
            return redirect()->route('profile.show')->with('success', 'Avatar updated successfully.');
            
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Upload failed'], 500);
            }
            
            return back()->withErrors(['avatar' => 'Upload failed. Please try again.']);
        }
    }

    /**
     * Process and store image with security measures.
     */
    private function processAndStoreImage($file, $filename)
    {
        try {
            // Process image with Intervention Image
            $image = Image::make($file->getPathname());
            
            // Remove EXIF data and resize if needed
            $image->resize(400, 400, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // Save processed image
            $path = storage_path('app/public/avatars/' . $filename);
            $image->save($path, 85); // 85% quality
            
        } catch (\Exception $e) {
            // Fallback to simple file storage if image processing fails
            $file->storeAs('avatars', $filename, 'public');
        }
    }

    /**
     * Enhanced security check for uploaded files.
     */
    private function isSecureImageFile($file)
    {
        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        // Check MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        // Check file signature (magic bytes)
        $handle = fopen($file->getPathname(), 'rb');
        $header = fread($handle, 8);
        fclose($handle);

        $signatures = [
            'jpeg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
            'gif' => ["GIF87a", "GIF89a"]
        ];

        $isValid = false;
        foreach ($signatures as $type => $sigs) {
            foreach ($sigs as $sig) {
                if (substr($header, 0, strlen($sig)) === $sig) {
                    $isValid = true;
                    break 2;
                }
            }
        }

        if (!$isValid) {
            return false;
        }

        // Check for suspicious content
        $content = file_get_contents($file->getPathname());
        $suspiciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar()
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        $user->update([
            'avatar' => null,
        ]);

        return redirect()->route('profile.show')->with('success', 'Avatar removed successfully.');
    }

    /**
     * @return array{phone: string, verified: bool}
     */
    private function profilePhoneVerificationState(Request $request): array
    {
        $state = $request->session()->get(self::PROFILE_PHONE_VERIFICATION_SESSION_KEY, []);

        if (! is_array($state)) {
            return ['phone' => '', 'verified' => false];
        }

        return [
            'phone' => (string) ($state['phone'] ?? ''),
            'verified' => (bool) ($state['verified'] ?? false),
        ];
    }

    private function storeProfilePhoneVerificationState(Request $request, string $phone, bool $verified): void
    {
        $request->session()->put(self::PROFILE_PHONE_VERIFICATION_SESSION_KEY, [
            'phone' => $phone,
            'verified' => $verified,
        ]);
    }

    private function normalizePhoneToE164(string $phone): ?string
    {
        return $this->phoneOtp->normalizePhoneToE164($phone);
    }
}
