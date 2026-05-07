<?php

namespace App\Http\Controllers;

use App\Mail\VendorQuestionnaireSubmittedMail;
use App\Models\Bid;
use App\Models\FileUpload;
use App\Models\VendorQuestionnaire;
use App\Models\VendorQuestionnaireDocument;
use App\Notifications\BidNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendorQuestionnaireController extends Controller
{
    /** Mandatory Yes/No questions in Part 1 — "No" blocks submission. */
    private const RESPONSIVE_REQUIRED_YES = [
        'p1_q4_licensed',
        'p1_q6_start_date',
        'p1_q7_coverage_hours',
        'p1_q8_staff_personnel',
        'p1_q9_uniform',
        'p1_q10_reporting',
        'p1_q11_technology_compliance',
        'p1_q12_insurance_minimums',
        'p1_q13_wage',
        'p1_q14_training',
        'p1_q15_response_time',
        'p1_q16_scope_reviewed',
        'p1_q17_terms_agreed',
        'p1_q18_pricing_accepted',
        'p1_q19_schedule_accepted',
        'p1_q20_pricing_sustainable',
    ];

    /** Part 2 disqualifiers — values listed must NOT match. */
    private const RESPONSIBLE_BLOCKERS_YES = [
        'p2_q19_failed_payroll',
        'p2_q20_lost_contract_staffing',
        'p2_q22_negligent_security_litigation',
        'p2_q23_license_suspended',
    ];

    private const RESPONSIBLE_REQUIRED_YES = [
        'p2_q24_three_references',
        'p2_q25_past_performance',
    ];

    /**
     * Resolve or create the questionnaire for a bid.
     * Vendor accepts → redirected here.
     */
    public function start(Request $request, Bid $bid): RedirectResponse
    {
        $this->authorizeVendor($request, $bid);

        $questionnaire = VendorQuestionnaire::firstOrCreate(
            ['bid_id' => $bid->id],
            [
                'vendor_id' => $bid->user_id,
                'job_posting_id' => $bid->job_posting_id,
                'status' => 'draft',
                'current_step' => 1,
                'responses' => [],
            ]
        );

        if ($questionnaire->wasRecentlyCreated) {
            $this->prefillDocumentsFromProfile($questionnaire);
        }

        return redirect()->route('vendor-questionnaires.show', [
            'questionnaire' => $questionnaire->id,
            'step' => $questionnaire->isSubmitted() ? VendorQuestionnaire::TOTAL_STEPS : $questionnaire->current_step,
        ]);
    }

    public function show(Request $request, VendorQuestionnaire $questionnaire, int $step = 1)
    {
        $this->authorizeVendorOwnsQuestionnaire($request, $questionnaire);

        $step = max(1, min(VendorQuestionnaire::TOTAL_STEPS, $step));

        if ($questionnaire->isSubmitted()) {
            return view('vendor-questionnaires.submitted', [
                'questionnaire' => $questionnaire->load('documents.fileUpload', 'jobPosting', 'bid'),
            ]);
        }

        $documents = $questionnaire->documents()->with('fileUpload')->get()->keyBy('document_type');

        return view('vendor-questionnaires.wizard', [
            'questionnaire' => $questionnaire,
            'step' => $step,
            'totalSteps' => VendorQuestionnaire::TOTAL_STEPS,
            'documents' => $documents,
            'documentTypes' => VendorQuestionnaire::DOCUMENT_TYPES,
            'responses' => $questionnaire->responses ?? [],
        ]);
    }

    public function saveStep(Request $request, VendorQuestionnaire $questionnaire, int $step): RedirectResponse
    {
        $this->authorizeVendorOwnsQuestionnaire($request, $questionnaire);

        if ($questionnaire->isSubmitted()) {
            return back()->with('error', 'This questionnaire has already been submitted.');
        }

        $step = max(1, min(VendorQuestionnaire::TOTAL_STEPS, $step));

        $responses = $questionnaire->responses ?? [];
        $stepData = $request->input('responses', []);
        if (is_array($stepData)) {
            $responses = array_merge($responses, $stepData);
        }

        if ($step === 1) {
            $this->handleStep1Uploads($request, $questionnaire);
        }

        $action = $request->input('action', 'next');

        $nextStep = match ($action) {
            'prev' => max(1, $step - 1),
            'save_exit' => $step,
            default => min(VendorQuestionnaire::TOTAL_STEPS, $step + 1),
        };

        $questionnaire->update([
            'responses' => $responses,
            'current_step' => max($questionnaire->current_step, $nextStep),
        ]);

        if ($action === 'save_exit') {
            return redirect()->route('home')->with('success', 'Questionnaire saved as draft. You can resume any time.');
        }

        return redirect()->route('vendor-questionnaires.show', [
            'questionnaire' => $questionnaire->id,
            'step' => $nextStep,
        ]);
    }

    public function submit(Request $request, VendorQuestionnaire $questionnaire): RedirectResponse
    {
        $this->authorizeVendorOwnsQuestionnaire($request, $questionnaire);

        if ($questionnaire->isSubmitted()) {
            return back()->with('error', 'This questionnaire has already been submitted.');
        }

        $responses = $questionnaire->responses ?? [];
        $documents = $questionnaire->documents()->pluck('document_type')->all();

        [$responsiveOk, $responsiveFailures] = $this->evaluateResponsive($responses, $documents);
        [$responsibleOk, $responsibleFailures] = $this->evaluateResponsible($responses);

        if (! $responsiveOk || ! $responsibleOk) {
            $questionnaire->update([
                'is_responsive' => $responsiveOk,
                'responsive_failures' => $responsiveFailures,
                'is_responsible' => $responsibleOk,
                'responsible_failures' => $responsibleFailures,
            ]);

            return redirect()
                ->route('vendor-questionnaires.show', ['questionnaire' => $questionnaire->id, 'step' => VendorQuestionnaire::TOTAL_STEPS])
                ->with('error', 'Submission blocked. Please review the issues listed below before submitting.')
                ->with('blocking_failures', array_merge($responsiveFailures, $responsibleFailures));
        }

        $token = Str::random(48);
        $questionnaire->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'is_responsive' => true,
            'responsive_failures' => [],
            'is_responsible' => true,
            'responsible_failures' => [],
            'buyer_review_token' => $token,
            'buyer_review_expires_at' => now()->addDays(14),
        ]);

        $this->dispatchBuyerEmail($questionnaire->fresh(['vendor', 'jobPosting.user', 'documents.fileUpload', 'bid']));

        $bid = $questionnaire->bid;
        if ($bid) {
            $bid->jobPosting?->user?->notify(new BidNotification($bid->fresh(), 'vendor_accepted'));
        }

        return redirect()->route('vendor-questionnaires.show', ['questionnaire' => $questionnaire->id])
            ->with('success', 'Questionnaire submitted to the buyer.');
    }

    /** Public buyer review via tokenized link (no auth). */
    public function buyerReview(Request $request, string $token)
    {
        $questionnaire = VendorQuestionnaire::where('buyer_review_token', $token)
            ->with(['vendor', 'jobPosting', 'documents.fileUpload', 'bid'])
            ->firstOrFail();

        if ($questionnaire->buyer_review_expires_at && $questionnaire->buyer_review_expires_at->isPast()) {
            abort(410, 'This review link has expired.');
        }

        return view('vendor-questionnaires.buyer-review', [
            'questionnaire' => $questionnaire,
            'documentTypes' => VendorQuestionnaire::DOCUMENT_TYPES,
        ]);
    }

    /** Admin read-only view. */
    public function adminShow(Request $request, VendorQuestionnaire $questionnaire)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        return view('vendor-questionnaires.admin-show', [
            'questionnaire' => $questionnaire->load(['vendor', 'jobPosting.user', 'documents.fileUpload', 'bid']),
            'documentTypes' => VendorQuestionnaire::DOCUMENT_TYPES,
        ]);
    }

    private function handleStep1Uploads(Request $request, VendorQuestionnaire $questionnaire): void
    {
        $files = $request->file('documents', []);
        if (! is_array($files)) {
            return;
        }

        foreach (VendorQuestionnaire::DOCUMENT_TYPES as $type => $label) {
            $file = $files[$type] ?? null;
            if (! $file) {
                continue;
            }

            $path = $file->store('vendor-questionnaire-documents/' . $questionnaire->id, 'public');

            $upload = FileUpload::create([
                'user_id' => $questionnaire->vendor_id,
                'filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'document_type' => $type,
                'uploaded_at' => now(),
            ]);

            VendorQuestionnaireDocument::updateOrCreate(
                [
                    'vendor_questionnaire_id' => $questionnaire->id,
                    'document_type' => $type,
                ],
                [
                    'file_upload_id' => $upload->id,
                    'prefilled_from_profile' => false,
                ]
            );
        }
    }

    private function prefillDocumentsFromProfile(VendorQuestionnaire $questionnaire): void
    {
        $existing = FileUpload::where('user_id', $questionnaire->vendor_id)
            ->whereIn('document_type', array_keys(VendorQuestionnaire::DOCUMENT_TYPES))
            ->orderByDesc('id')
            ->get()
            ->unique('document_type');

        foreach ($existing as $upload) {
            VendorQuestionnaireDocument::updateOrCreate(
                [
                    'vendor_questionnaire_id' => $questionnaire->id,
                    'document_type' => $upload->document_type,
                ],
                [
                    'file_upload_id' => $upload->id,
                    'prefilled_from_profile' => true,
                ]
            );
        }
    }

    private function evaluateResponsive(array $responses, array $documents): array
    {
        $failures = [];

        foreach (self::RESPONSIVE_REQUIRED_YES as $key) {
            if (($responses[$key] ?? null) !== 'yes') {
                $failures[] = ['key' => $key, 'reason' => 'Mandatory item not confirmed.'];
            }
        }

        $missingDocs = array_diff(
            array_keys(VendorQuestionnaire::DOCUMENT_TYPES),
            $documents
        );
        foreach ($missingDocs as $type) {
            $failures[] = [
                'key' => "doc_{$type}",
                'reason' => 'Required document missing: ' . VendorQuestionnaire::DOCUMENT_TYPES[$type],
            ];
        }

        $tech = $responses['p1_q11_technology'] ?? [];
        if (! is_array($tech) || count($tech) === 0) {
            $failures[] = ['key' => 'p1_q11_technology', 'reason' => 'Select at least one technology capability.'];
        }

        return [count($failures) === 0, $failures];
    }

    private function evaluateResponsible(array $responses): array
    {
        $failures = [];

        foreach (self::RESPONSIBLE_BLOCKERS_YES as $key) {
            if (($responses[$key] ?? null) === 'yes') {
                $failures[] = ['key' => $key, 'reason' => 'Disqualifying answer.'];
            }
        }

        foreach (self::RESPONSIBLE_REQUIRED_YES as $key) {
            if (($responses[$key] ?? null) !== 'yes') {
                $failures[] = ['key' => $key, 'reason' => 'Required confirmation missing.'];
            }
        }

        $insurances = $responses['p2_q21_insurances'] ?? [];
        if (! is_array($insurances)) {
            $insurances = [];
        }
        foreach (['workers_comp', 'general_liability'] as $required) {
            if (! in_array($required, $insurances, true)) {
                $failures[] = [
                    'key' => 'p2_q21_insurances',
                    'reason' => $required === 'workers_comp'
                        ? "Workers' Compensation Insurance is required."
                        : 'General Liability Insurance is required.',
                ];
            }
        }

        if (($responses['p2_q18_payroll_30_45'] ?? null) !== 'yes') {
            $failures[] = ['key' => 'p2_q18_payroll_30_45', 'reason' => '30–45 day payroll sustainment is required.'];
        }

        return [count($failures) === 0, $failures];
    }

    private function dispatchBuyerEmail(VendorQuestionnaire $questionnaire): void
    {
        $buyer = $questionnaire->jobPosting?->user;
        if (! $buyer || ! $buyer->email) {
            return;
        }

        $pdf = Pdf::loadView('pdf.vendor-questionnaire', [
            'questionnaire' => $questionnaire,
            'documentTypes' => VendorQuestionnaire::DOCUMENT_TYPES,
        ])->output();

        $filename = 'vendor-questionnaire-' . $questionnaire->id . '.pdf';

        $attachments = [];
        foreach ($questionnaire->documents as $doc) {
            $upload = $doc->fileUpload;
            if (! $upload) {
                continue;
            }
            if (! Storage::disk('public')->exists($upload->file_path)) {
                continue;
            }
            $attachments[] = [
                'data' => Storage::disk('public')->get($upload->file_path),
                'name' => $upload->filename,
                'mime' => $upload->mime_type ?? 'application/octet-stream',
            ];
        }

        Mail::to($buyer->email)->send(new VendorQuestionnaireSubmittedMail(
            $questionnaire,
            $pdf,
            $filename,
            $attachments,
        ));
    }

    private function authorizeVendor(Request $request, Bid $bid): void
    {
        $user = $request->user();
        if (! $user || ! $user->isVendor() || $bid->user_id !== $user->id) {
            abort(403);
        }
        if (($bid->vendor_response_status ?? null) !== 'accepted') {
            abort(403, 'Questionnaire is only available after accepting an offer.');
        }
    }

    private function authorizeVendorOwnsQuestionnaire(Request $request, VendorQuestionnaire $questionnaire): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if ($user->isAdmin()) {
            return;
        }
        if (! $user->isVendor() || $questionnaire->vendor_id !== $user->id) {
            abort(403);
        }
    }
}
