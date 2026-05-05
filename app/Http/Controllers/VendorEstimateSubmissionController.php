<?php

namespace App\Http\Controllers;

use App\Mail\VendorEstimateSubmittedMail;
use App\Models\FeatureUsageRule;
use App\Models\JobPosting;
use App\Models\VendorEstimateSubmission;
use App\Services\WalletService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VendorEstimateSubmissionController extends Controller
{
    public const FEATURE_KEY = 'vendor_estimate_submission';

    public function __construct(private WalletService $wallet) {}

    public function openJobs(Request $request): JsonResponse
    {
        if (! $request->user()?->isVendor()) {
            return response()->json(['jobs' => []], 200);
        }

        $jobs = JobPosting::query()
            ->whereNull('hired_at')
            ->whereNull('closed_at')
            ->whereNotIn('status', ['closed', 'awarded'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('user:id,name,company')
            ->latest()
            ->limit(50)
            ->get(['id', 'user_id', 'title', 'category', 'location', 'created_at']);

        return response()->json([
            'jobs' => $jobs->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->category,
                'location' => $job->location,
                'buyer_name' => $job->user?->name,
                'buyer_company' => $job->user?->company,
                'posted_on' => optional($job->created_at)->format('M j, Y'),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user?->isVendor()) {
            return response()->json(['ok' => false, 'message' => 'Only vendors can submit estimates.'], 403);
        }

        $data = $request->validate([
            'job_posting_id' => ['required', 'integer', 'exists:job_postings,id'],
            'snapshot' => ['required', 'array'],
            'snapshot.service_label' => ['nullable', 'string', 'max:255'],
            'snapshot.location' => ['nullable', 'string', 'max:255'],
            'snapshot.notes' => ['nullable', 'string', 'max:4000'],
            'snapshot.rows' => ['nullable', 'array'],
            'snapshot.totals' => ['nullable', 'array'],
        ]);

        $rule = FeatureUsageRule::query()
            ->where('feature_key', self::FEATURE_KEY)
            ->where('is_active', true)
            ->first();

        $cost = (int) ($rule?->tokens_required ?? 50);
        $balance = $this->wallet->getBalance($user);

        if ($balance < $cost) {
            return response()->json([
                'ok' => false,
                'error' => 'insufficient_balance',
                'required' => $cost,
                'current_balance' => $balance,
                'redirect_url' => route('credits'),
                'message' => "You need {$cost} credits to submit an estimate. Current balance: {$balance}.",
            ], 402);
        }

        $job = JobPosting::with('user')->findOrFail($data['job_posting_id']);
        if (! $job->user) {
            return response()->json(['ok' => false, 'message' => 'Buyer not found for this job.'], 422);
        }

        $this->wallet->spendTokens(
            $user,
            $cost,
            self::FEATURE_KEY,
            "Vendor estimate submission for job #{$job->id}",
            (string) $job->id,
        );

        $submission = VendorEstimateSubmission::create([
            'vendor_id' => $user->id,
            'job_posting_id' => $job->id,
            'buyer_id' => $job->user_id,
            'snapshot' => $data['snapshot'],
            'access_token' => Str::random(48),
            'credits_spent' => $cost,
        ]);

        $submission->load(['vendor', 'buyer', 'jobPosting']);

        $pdf = Pdf::loadView('pdf.vendor-estimate-submission', ['submission' => $submission]);
        $pdfBinary = $pdf->output();
        $filename = 'estimate-' . $submission->id . '.pdf';
        $pdfPath = "vendor-estimate-submissions/{$submission->id}/{$filename}";
        Storage::disk('local')->put($pdfPath, $pdfBinary);
        $submission->update(['pdf_path' => $pdfPath]);

        $viewUrl = route('vendor-estimate-submissions.show', [
            'submission' => $submission->id,
            'token' => $submission->access_token,
        ]);

        if ($job->user->email) {
            Mail::to($job->user->email)->send(
                new VendorEstimateSubmittedMail($submission, $pdfBinary, $filename, $viewUrl)
            );
            $submission->update(['emailed_at' => now()]);
        }

        return response()->json([
            'ok' => true,
            'submission_id' => $submission->id,
            'view_url' => $viewUrl,
            'credits_spent' => $cost,
            'balance_after' => $this->wallet->getBalance($user),
            'message' => 'Estimate sent to ' . ($job->user->name ?? 'the buyer') . '.',
        ]);
    }

    public function show(Request $request, VendorEstimateSubmission $submission): View
    {
        $token = (string) $request->query('token', '');
        $user = $request->user();

        $isOwnerVendor = $user && $user->id === $submission->vendor_id;
        $isOwnerBuyer = $user && $user->id === $submission->buyer_id;
        $hasValidToken = $token !== '' && hash_equals($submission->access_token, $token);

        if (! $isOwnerVendor && ! $isOwnerBuyer && ! $hasValidToken) {
            abort(403);
        }

        $submission->load(['vendor', 'buyer', 'jobPosting']);

        if ($submission->viewed_at === null && ! $isOwnerVendor) {
            $submission->update(['viewed_at' => now()]);
        }

        return view('pages.vendor-estimate-submission', compact('submission'));
    }
}
