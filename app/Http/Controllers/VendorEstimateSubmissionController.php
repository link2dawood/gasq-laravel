<?php

namespace App\Http\Controllers;

use App\Mail\VendorEstimateSubmittedMail;
use App\Models\FeatureUsageRule;
use App\Models\JobPosting;
use App\Models\Transaction;
use App\Models\VendorEstimateSubmission;
use App\Services\EstimateCreditPricingService;
use App\Services\WalletService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VendorEstimateSubmissionController extends Controller
{
    public const FEATURE_KEY = 'vendor_estimate_submission';

    public function __construct(
        private WalletService $wallet,
        private EstimateCreditPricingService $pricing,
    ) {}

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

        $job = JobPosting::with('user')->findOrFail($data['job_posting_id']);
        if (! $job->user) {
            return response()->json(['ok' => false, 'message' => 'Buyer not found for this job.'], 422);
        }

        // Credits scale with the estimate's project value (Procurement Credit ladder).
        // Prefer the value on the estimate snapshot; fall back to the job's budget, then
        // to the configured flat rule if neither is available. A sub-$1k figure is a
        // stray row (e.g. an hourly bill rate), not a real project value — ignore it.
        $projectValue = $this->pricing->projectValueFromSnapshot($data['snapshot']);
        if ($projectValue < 1000) {
            $projectValue = (float) ($job->budget_max ?? $job->budget_min ?? 0);
        }
        $cost = $projectValue > 0
            ? $this->pricing->creditsFor($projectValue)
            : (int) (FeatureUsageRule::query()
                ->where('feature_key', self::FEATURE_KEY)
                ->where('is_active', true)
                ->value('tokens_required') ?? 1);

        // Idempotency: an identical estimate to the same job is charged once. A repeat
        // POST (double-click / retry) replays the original submission without debiting.
        $idempotencyKey = 'estimate_submission:' . $user->id . ':' . $job->id . ':' . sha1((string) json_encode($data['snapshot']));

        if ($existing = $this->findChargedSubmission($idempotencyKey, $user->id, $job->id)) {
            return $this->duplicateResponse($existing);
        }

        if ($this->wallet->getBalance($user) < $cost) {
            $balance = $this->wallet->getBalance($user);

            return response()->json([
                'ok' => false,
                'error' => 'insufficient_balance',
                'required' => $cost,
                'current_balance' => $balance,
                'redirect_url' => route('credits'),
                'message' => "You need {$cost} credits to submit an estimate. Current balance: {$balance}.",
            ], 402);
        }

        // Create the submission and charge atomically, keyed for idempotency, so a
        // failed charge never leaves an unpaid submission behind.
        try {
            $submission = DB::transaction(function () use ($user, $job, $data, $cost, $idempotencyKey) {
                $submission = VendorEstimateSubmission::create([
                    'vendor_id' => $user->id,
                    'job_posting_id' => $job->id,
                    'buyer_id' => $job->user_id,
                    'snapshot' => $data['snapshot'],
                    'access_token' => Str::random(48),
                    'credits_spent' => $cost,
                ]);

                $charged = $this->wallet->spendTokens(
                    $user,
                    $cost,
                    self::FEATURE_KEY,
                    "Vendor estimate submission for job #{$job->id}",
                    (string) $submission->id,
                    $idempotencyKey,
                );

                if (! $charged) {
                    // Balance was spent between the check above and here.
                    throw new \RuntimeException('insufficient_balance');
                }

                return $submission;
            });
        } catch (QueryException $e) {
            // Concurrent identical submit hit the idempotency unique index → replay original.
            if ($existing = $this->findChargedSubmission($idempotencyKey, $user->id, $job->id)) {
                return $this->duplicateResponse($existing);
            }
            throw $e;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'insufficient_balance') {
                throw $e;
            }
            $balance = $this->wallet->getBalance($user);

            return response()->json([
                'ok' => false,
                'error' => 'insufficient_balance',
                'required' => $cost,
                'current_balance' => $balance,
                'redirect_url' => route('credits'),
                'message' => "You need {$cost} credits to submit an estimate. Current balance: {$balance}.",
            ], 402);
        }

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

    /**
     * Find the submission already paid for under this idempotency key, if any.
     */
    private function findChargedSubmission(string $idempotencyKey, int $vendorId, int $jobId): ?VendorEstimateSubmission
    {
        $tx = Transaction::query()->where('idempotency_key', $idempotencyKey)->first();
        if (! $tx) {
            return null;
        }

        $with = ['vendor', 'buyer', 'jobPosting.user'];

        if (is_numeric($tx->reference_id)) {
            $found = VendorEstimateSubmission::with($with)->whereKey((int) $tx->reference_id)->first();
            if ($found) {
                return $found;
            }
        }

        return VendorEstimateSubmission::with($with)
            ->where('vendor_id', $vendorId)
            ->where('job_posting_id', $jobId)
            ->latest('id')
            ->first();
    }

    /**
     * Success-shaped response for a replayed (already-charged) submission.
     */
    private function duplicateResponse(VendorEstimateSubmission $submission): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'submission_id' => $submission->id,
            'view_url' => route('vendor-estimate-submissions.show', [
                'submission' => $submission->id,
                'token' => $submission->access_token,
            ]),
            'credits_spent' => (int) $submission->credits_spent,
            'balance_after' => $submission->vendor ? $this->wallet->getBalance($submission->vendor) : null,
            'duplicate' => true,
            'message' => 'This estimate was already submitted; no additional credits were charged.',
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
