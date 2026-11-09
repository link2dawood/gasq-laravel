<?php

namespace App\Http\Controllers;

use App\Mail\VendorPdfVerificationMail;
use App\Models\VendorEstimateDelivery;
use App\Models\VendorPdfAccessEvent;
use App\Services\EmailOtpService;
use App\Services\VendorPdfPasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorPdfPasswordController extends Controller
{
    public function release(Request $request, VendorEstimateDelivery $delivery, VendorPdfPasswordService $service)
    {
        $this->owner($request, $delivery);
        $data = $request->validate(['appointment_id' => ['nullable', Rule::requiredIf($delivery->native_scheduling), 'integer'], 'review_completed' => ['accepted'], 'pdf_password' => ['required', 'string', 'min:8', 'max:32'], 'external_review_reference' => ['nullable', 'string', 'max:255'], 'external_review_started_at' => ['nullable', 'date'], 'external_review_ended_at' => ['nullable', 'date']]);
        $service->release($delivery, $request->user(), $data['appointment_id'] ?? null, $data['pdf_password'], $data);

        return back()->with('status', 'Password released to the verified buyer access page for 30 days. Release date and vendor have been recorded.');
    }

    public function revoke(Request $request, VendorEstimateDelivery $delivery, VendorPdfPasswordService $service)
    {
        $this->owner($request, $delivery);
        $service->revoke($delivery, $request->user());

        return back()->with('status', 'Online access revoked. Previously downloaded PDFs and disclosed passwords cannot be recalled.');
    }

    public function show(Request $request, string $token)
    {
        $delivery = $this->resolve($token);
        VendorPdfAccessEvent::record($delivery, 'access_page_viewed');

        return $this->page($request, $delivery);
    }

    public function code(Request $request, string $token, EmailOtpService $otp, VendorPdfPasswordService $passwords)
    {
        $delivery = $this->resolve($token);
        $passwords->ensureAvailable($delivery);
        abort_if(! app()->environment('testing') && in_array(config('mail.default'), ['array', 'log'], true), 503, 'Email delivery is not configured.');
        $result = DB::transaction(function () use ($delivery, $otp) {
            VendorEstimateDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            return $otp->issue('vendor_pdf:'.$delivery->id, $delivery->recipient);
        });
        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }
        try {
            Mail::to($delivery->recipient)->send(new VendorPdfVerificationMail($result['code'], $delivery->snapshot['number']));
        } catch (\Throwable) {
            VendorPdfAccessEvent::record($delivery, 'verification_mail_failed');

            return back()->withErrors(['code' => 'The verification email could not be sent. Please try again later.']);
        }
        VendorPdfAccessEvent::record($delivery, 'verification_code_sent');

        return back()->with('status', 'A verification code was sent to the original buyer email. It expires in 10 minutes.');
    }

    public function verify(Request $request, string $token, EmailOtpService $otp, VendorPdfPasswordService $passwords)
    {
        $delivery = $this->resolve($token);
        $passwords->ensureAvailable($delivery);
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $result = DB::transaction(function () use ($delivery, $otp, $data) {
            VendorEstimateDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            return $otp->check('vendor_pdf:'.$delivery->id, $delivery->recipient, $data['code']);
        });
        VendorPdfAccessEvent::record($delivery, $result['ok'] ? 'buyer_verified' : 'verification_failed');
        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }
        $request->session()->regenerate();
        $request->session()->put('vendor_pdf_verified.'.$delivery->id, now()->addMinutes(30)->timestamp);

        return redirect()->to($delivery->accessUrl());
    }

    public function reveal(Request $request, string $token, VendorPdfPasswordService $passwords)
    {
        $delivery = $this->resolve($token);
        $passwords->ensureAvailable($delivery);
        $this->verified($request, $delivery);
        VendorPdfAccessEvent::record($delivery, 'password_revealed');

        return $this->page($request, $delivery, $delivery->released_password);
    }

    public function download(Request $request, string $token, VendorPdfPasswordService $passwords)
    {
        $delivery = $this->resolve($token);
        $passwords->ensureAvailable($delivery);
        $this->verified($request, $delivery);
        try {
            $bytes = Storage::disk('local')->get($delivery->pdf_path);
        } catch (\Throwable) {
            VendorPdfAccessEvent::record($delivery, 'pdf_unavailable');
            abort(409, 'The protected file is unavailable. Contact your vendor.');
        }
        if (! is_string($bytes) || ! hash_equals($delivery->pdf_sha256, hash('sha256', $bytes))) {
            VendorPdfAccessEvent::record($delivery, 'pdf_integrity_failed');
            abort(409, 'The protected file failed verification. Contact your vendor.');
        }
        VendorPdfAccessEvent::record($delivery, 'pdf_downloaded');

        return response($bytes)->header('Content-Type', 'application/pdf')->header('Content-Disposition', 'attachment; filename="'.$delivery->snapshot['number'].'.pdf"')->header('Cache-Control', 'private, no-store');
    }

    private function resolve(string $token): VendorEstimateDelivery
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);

        return VendorEstimateDelivery::where('scheduling_token_hash', hash('sha256', $token))->whereIn('status', ['sent', 'uncertain'])->firstOrFail();
    }

    private function page(Request $request, VendorEstimateDelivery $delivery, ?string $revealedPassword = null)
    {
        $verified = $request->session()->get('vendor_pdf_verified.'.$delivery->id, 0) > now()->timestamp;

        return response()->view('vendor-estimates.access', compact('delivery', 'verified', 'revealedPassword'))->header('Cache-Control', 'private, no-store')->header('Pragma', 'no-cache')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function verified(Request $request, VendorEstimateDelivery $delivery): void
    {
        if ($request->session()->get('vendor_pdf_verified.'.$delivery->id, 0) <= now()->timestamp) {
            VendorPdfAccessEvent::record($delivery, 'verification_required');
            abort(403, 'Verify the original buyer email before accessing this PDF.');
        }
    }

    private function owner(Request $request, VendorEstimateDelivery $delivery): void
    {
        abort_unless($request->user()->isVendor() && $delivery->estimate->vendor_id === $request->user()->id, 404);
    }
}
