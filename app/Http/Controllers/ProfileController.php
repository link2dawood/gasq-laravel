<?php

namespace App\Http\Controllers;

use App\Services\PhoneOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        if (Auth::user()?->isVendor()) {
            return redirect()->route('profile.edit');
        }

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

        if ($user?->isVendor()) {
            $profile = $user->vendorProfile;
            $capability = $user->vendorCapability;

            return view('profile.edit-vendor', [
                'user' => $user,
                'profile' => $profile,
                'capability' => $capability,
                'additionalInfo' => is_array($capability?->additional_info) ? $capability->additional_info : [],
                'phoneVerification' => $phoneVerification,
                'vendorSettingsOptions' => $this->vendorSettingsOptions(),
            ]);
        }

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

        if ($user?->isVendor()) {
            return $this->updateVendorProfile($request, $user);
        }

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

    private function updateVendorProfile(Request $request, $user): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'business_license_number' => ['nullable', 'string', 'max:255'],
            'license_expiration_date' => ['nullable', 'date'],
            'vendor_ein' => ['nullable', 'string', 'max:100'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:200'],
            'profile_description' => ['nullable', 'string'],
            'insurance.general.limits_covered' => ['nullable', 'string', 'max:100'],
            'insurance.general.deductible_per_occurrence' => ['nullable', 'string', 'max:100'],
            'insurance.general.company_name' => ['nullable', 'string', 'max:255'],
            'insurance.general.company_address' => ['nullable', 'string', 'max:500'],
            'insurance.general.policy_number' => ['nullable', 'string', 'max:255'],
            'insurance.general.carrier_phone' => ['nullable', 'string', 'max:50'],
            'insurance.general.agent_name' => ['nullable', 'string', 'max:255'],
            'insurance.general.agent_email' => ['nullable', 'email', 'max:255'],
            'insurance.workers_comp.company_name' => ['nullable', 'string', 'max:255'],
            'insurance.workers_comp.company_address' => ['nullable', 'string', 'max:500'],
            'insurance.workers_comp.policy_number' => ['nullable', 'string', 'max:255'],
            'insurance.workers_comp.carrier_phone' => ['nullable', 'string', 'max:50'],
            'insurance.workers_comp.agent_name' => ['nullable', 'string', 'max:255'],
            'insurance.workers_comp.agent_email' => ['nullable', 'email', 'max:255'],
            'works_other_states' => ['nullable', 'in:yes,no'],
            'service_capabilities' => ['nullable', 'array'],
            'service_capabilities.*' => ['string', 'max:255'],
            'full_time_employees' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'part_time_employees' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'uses_gps_monitoring' => ['nullable', 'in:yes,no'],
            'uses_guard_management_software' => ['nullable', 'in:yes,no'],
            'uses_tasers' => ['nullable', 'in:yes,no'],
            'uses_body_cameras' => ['nullable', 'in:yes,no'],
            'uses_incident_reporting_software' => ['nullable', 'in:yes,no'],
            'uses_drones' => ['nullable', 'in:yes,no'],
            'uses_1099_employees' => ['nullable', 'in:yes,no'],
            'has_dispatch_center' => ['nullable', 'in:yes,no'],
            'branch_office_scope' => ['nullable', 'in:local_only,statewide,nationwide'],
            'certifications_flags' => ['nullable', 'array'],
            'certifications_flags.*' => ['string', 'max:50'],
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

        $serviceCapabilities = array_values(array_filter((array) $request->input('service_capabilities', [])));
        $certificationFlags = array_values(array_filter((array) $request->input('certifications_flags', [])));

        DB::transaction(function () use (
            $request,
            $user,
            $submittedPhone,
            $normalizedSubmittedPhone,
            $normalizedCurrentPhone,
            $serviceCapabilities,
            $certificationFlags
        ): void {
            $user->update([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'company' => $request->input('company') ?: $request->input('company_name'),
                'phone' => $submittedPhone !== '' ? $normalizedSubmittedPhone : null,
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'zip_code' => $request->input('zip_code'),
                'phone_verified' => $submittedPhone === ''
                    ? false
                    : ($normalizedSubmittedPhone === $normalizedCurrentPhone ? (bool) $user->phone_verified : true),
            ]);

            $profile = $user->vendorProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $request->input('company_name') ?: $request->input('company') ?: $user->company,
                    'description' => $request->input('profile_description'),
                    'phone' => $submittedPhone !== '' ? $normalizedSubmittedPhone : null,
                    'address' => $request->input('street_address'),
                    'capabilities' => $serviceCapabilities,
                ]
            );

            $additionalInfo = [
                'license_expiration_date' => $request->input('license_expiration_date'),
                'vendor_ein' => $request->input('vendor_ein'),
                'works_other_states' => $request->input('works_other_states'),
                'full_time_employees' => $request->integer('full_time_employees') ?: null,
                'part_time_employees' => $request->integer('part_time_employees') ?: null,
                'uses_gps_monitoring' => $request->input('uses_gps_monitoring'),
                'uses_guard_management_software' => $request->input('uses_guard_management_software'),
                'uses_tasers' => $request->input('uses_tasers'),
                'uses_body_cameras' => $request->input('uses_body_cameras'),
                'uses_incident_reporting_software' => $request->input('uses_incident_reporting_software'),
                'uses_drones' => $request->input('uses_drones'),
                'uses_1099_employees' => $request->input('uses_1099_employees'),
                'has_dispatch_center' => $request->input('has_dispatch_center'),
                'branch_office_scope' => $request->input('branch_office_scope'),
                'insurance' => [
                    'general' => (array) $request->input('insurance.general', []),
                    'workers_comp' => (array) $request->input('insurance.workers_comp', []),
                ],
                'certifications_flags' => $certificationFlags,
            ];

            $user->vendorCapability()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'legal_business_name' => $request->input('company_name') ?: $request->input('company') ?: $user->company,
                    'business_address' => $request->input('street_address'),
                    'business_license_number' => $request->input('business_license_number'),
                    'core_competencies' => $serviceCapabilities,
                    'years_of_experience' => $request->input('years_of_experience'),
                    'general_liability_insurance' => $request->input('insurance.general.company_name'),
                    'workers_comp_coverage' => $request->input('insurance.workers_comp.company_name'),
                    'additional_info' => array_filter($additionalInfo, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []),
                    'certifications' => $certificationFlags,
                    'states_licensed' => $request->input('works_other_states') === 'yes'
                        ? array_values(array_filter([$request->input('state')]))
                        : [],
                    'service_areas' => array_values(array_filter([$request->input('city'), $request->input('state'), $request->input('zip_code')])),
                    'team_size' => trim(implode(' / ', array_filter([
                        $request->filled('full_time_employees') ? 'FT ' . $request->input('full_time_employees') : null,
                        $request->filled('part_time_employees') ? 'PT ' . $request->input('part_time_employees') : null,
                    ]))) ?: null,
                ]
            );
        });

        $request->session()->forget(self::PROFILE_PHONE_VERIFICATION_SESSION_KEY);

        return redirect()->route('profile.edit')->with('success', 'Vendor settings updated successfully.');
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

    /**
     * @return array<string, array<int, string>|array<string, string>>
     */
    private function vendorSettingsOptions(): array
    {
        return [
            'limits_covered' => ['$500K/$1M', '$1M/$1M', '$1M/$2M', 'Other'],
            'deductibles' => ['$1,000', '$2,500', '$5,000', '$10,000', 'Over $10,000'],
            'service_capabilities' => [
                'Guard & Patrol Services Both Armed & Unarmed',
                'Guard & Patrol Services Unarmed Only',
                'Guard & Patrol Services Armed Only',
                'Remote Video Monitoring',
                'Mobile Patrol Services - Unarmed',
                'Mobile Patrol Services - Armed',
                'Special Event Security Services - Unarmed',
                'Special Event Security Services - Armed',
                'Off Duty Police Officer',
                'Executive Protection Agent',
                'Loss Prevention Agents',
                'Fire watch Officer',
                'K-9 Support',
            ],
            'branch_office_scope' => [
                'local_only' => 'Local Only',
                'statewide' => 'Statewide',
                'nationwide' => 'Nationwide',
            ],
            'certifications_flags' => [
                '8a_certified' => '8a Certified',
                'gsa_certified' => 'GSA Certified',
                'veteran_owned' => 'Veteran-owned Business',
                'woman_owned' => 'Woman Owned Business',
                'minority_owned' => 'Minority Owned Business',
                'small_local_business' => 'Small Local Business',
                'hub_zone_business' => 'HUB Zone Business',
                'small_business_enterprise' => 'Small Business Enterprise',
                'disadvantaged_business_enterprise' => 'Disadvantage Business Enterprise',
                'small_disadvantaged_business' => 'Small Disadvantage Business',
                'emerging_business_enterprise' => 'Emerging Business Enterprise',
                'lgbtq_business_enterprise' => 'LGBTQ Business Enterprise',
            ],
        ];
    }
}
