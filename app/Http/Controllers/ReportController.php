<?php

namespace App\Http\Controllers;

use App\Mail\ReportPdfMail;
use App\Models\CalculatorState;
use App\Models\Transaction;
use App\Services\CostToProtectEstimate;
use App\Services\ReportService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    /**
     * Internal inbox BCC'd on every estimate/report emailed from the site so
     * GASQ retains a copy of each one sent. Change here to update globally.
     */
    private const ESTIMATE_BCC = 'info@getasecurityquotenow.com';

    public function __construct(
        private ReportService $report,
        private WalletService $wallet,
    ) {}

    /**
     * Calculator types billed per generated report (charge once at download/email,
     * free to edit/preview, free to re-take the same report). Other calculators
     * keep their existing billing.
     */
    private const REPORT_BILLED_TYPES = ['budget-calculator', 'budget-calculator-allocation'];

    /**
     * Charge credits once per unique report version. Editing inputs produces a
     * new version (new charge); re-downloading/emailing the same inputs is free.
     * Returns a redirect when credits are insufficient, otherwise null.
     */
    private function chargeForReport(Request $request, string $type, array $payload): ?\Illuminate\Http\RedirectResponse
    {
        if (! in_array($type, self::REPORT_BILLED_TYPES, true)) {
            return null;
        }

        $meta = (array) data_get($payload, 'scenario.meta', []);
        unset($meta['contact'], $meta['inputs']); // contact/master-input tweaks aren't a new report
        $hash = md5($type . '|' . json_encode($meta));

        $paid = (array) session('paid_report_hashes', []);
        if (in_array($hash, $paid, true)) {
            return null; // already paid for this exact report — re-download/email is free
        }

        $cost = (int) config('credits.calculator_per_run');
        $spent = $this->wallet->spendTokens(
            $request->user(),
            $cost,
            'budget_calculator_report',
            "GASQ report generated ({$cost} credits): {$type}",
            null,
        );
        if (! $spent) {
            return back()->with('needs_credits', $cost);
        }

        $paid[] = $hash;
        session(['paid_report_hashes' => array_slice($paid, -200)]);

        return null;
    }

    /**
     * Download receipt PDF for a credit purchase (user's own transaction).
     */
    public function downloadReceipt(Request $request, Transaction $transaction): Response
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }
        if ($transaction->type !== 'purchase') {
            abort(404, 'Receipt only available for credit purchases.');
        }

        $pdf = $this->report->receiptPdf($transaction);
        return $pdf->download($this->report->filenameForReceipt($transaction));
    }

    /**
     * Download calculator report PDF (uses last result from session).
     */
    public function downloadReport(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $type = $request->input('type');
        $payload = $this->payloadForType($request, $type);
        if (! $type || ! $payload) {
            return back()->with('error', 'No report data available. Run the calculator again and use Download PDF.');
        }

        if ($charge = $this->chargeForReport($request, $type, $payload)) {
            return $charge;
        }

        $pdf = $this->report->calculatorPdf($type, $payload, $this->openPassword($request));
        return $pdf->download($this->report->filenameForCalculator($type, $request->user()));
    }

    /**
     * Email calculator report PDF.
     */
    public function emailReport(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:instant-estimator,main-menu,contract-analysis,security-billing,mobile-patrol,mobile-patrol-buyer,mobile-patrol-comparison,mobile-patrol-hit-calculator,mobile-patrol-analysis,gasq-tco-calculator,government-contract-calculator,budget-calculator,budget-calculator-preview,budget-calculator-allocation,economic-justification,bill-rate-analysis,workforce-appraisal-report,buyer-fit-index,gasq-direct-labor-build-up,gasq-additional-cost-stack',
            // Password the recipient must type to OPEN the PDF (vendor's choice,
            // passed to the buyer out of band). Blank ⇒ the file opens normally.
            'pdf_password' => 'nullable|string|max:32',
            'email' => 'required|string',
            'email2' => 'nullable|string',
            // Optional on-site survey notes + photos/files (preparers only, gated below).
            'notes' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:8',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,heic,heif,webp,gif,pdf,doc,docx,txt|max:8192',
        ]);

        // Combine the primary field (which may itself hold several addresses) with
        // the optional second-email field, then split on comma/semicolon/space.
        $rawEmails = trim((string) $request->input('email')) . ',' . trim((string) $request->input('email2'));
        $recipients = collect(preg_split('/[,;\s]+/', $rawEmails, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($e) => trim($e))
            ->unique()
            ->values();
        $invalid = $recipients->reject(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($recipients->isEmpty() || $invalid->isNotEmpty()) {
            $error = $invalid->isNotEmpty()
                ? 'These email addresses look invalid: ' . $invalid->implode(', ')
                : 'Enter at least one valid email address.';

            return $this->emailFailure($request, $error);
        }

        $type = $request->input('type');
        $payload = $this->payloadForType($request, $type);
        if (! $payload) {
            return $this->emailFailure($request, 'No report data available. Run the calculator again and use Email report.');
        }

        if ($charge = $this->chargeForReport($request, $type, $payload)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'needs_credits' => (int) config('credits.calculator_per_run'),
                    'message' => 'You do not have enough credits to generate this report.',
                ], 402);
            }

            return $charge;
        }

        $filename = $this->report->filenameForCalculator($type, $request->user());
        $subject = 'Your GASQ Calculator Report – ' . str_replace('-', ' ', ucfirst($type));

        // The Workforce/Budget report ships the branded "Cost to Protect" cover
        // email; everything else uses the short generic body.
        [$bodyView, $bodyData] = $this->emailBodyFor($type, $payload);
        if ($bodyView === 'emails.cost-to-protect') {
            $subject = 'Your GASQ Cost to Protect™ Appraisal Report';
        }
        if ($bodyView === 'emails.cost-to-protect-locked') {
            $subject = 'Your GASQ Cost to Protect™ Estimate — Locked Preview';
        }

        // On-site survey notes + photos/files. Only preparers (vendors/admins) may
        // attach these — re-checked server-side so a crafted request can't bypass
        // the hidden UI. Notes render in the email body; files ride as attachments.
        $user = $request->user();
        $canAttach = $user && (
            (method_exists($user, 'isVendor') && $user->isVendor())
            || (method_exists($user, 'isAdmin') && $user->isAdmin())
        );

        $extraAttachments = [];
        if ($canAttach) {
            $notes = trim((string) $request->input('notes', ''));
            foreach ((array) $request->file('attachments', []) as $file) {
                if ($file && $file->isValid()) {
                    $extraAttachments[] = [
                        'data' => (string) file_get_contents($file->getRealPath()),
                        'name' => $file->getClientOriginalName() ?: ('attachment.' . ($file->guessExtension() ?: 'dat')),
                        'mime' => $file->getMimeType() ?: 'application/octet-stream',
                    ];
                }
            }
            if ($notes !== '' || $extraAttachments !== []) {
                $bodyData = array_merge($bodyData, [
                    'surveyorNotes' => $notes !== '' ? $notes : null,
                    'attachmentCount' => count($extraAttachments),
                ]);
            }
        }

        // BCC the GASQ inbox (record of each send) and the HubSpot "log to CRM"
        // address (auto-logs the report onto the contact's HubSpot timeline).
        $bcc = array_values(array_filter([self::ESTIMATE_BCC, config('services.hubspot.bcc')]));

        // Send each recipient their own copy (so they don't see each other),
        // stamped "Prepared exclusively for <their email>" so any forwarded copy
        // is traceable.
        foreach ($recipients as $to) {
            $pdf = $this->report->calculatorPdf(
                $type,
                array_merge($payload, ['preparedForEmail' => $to]),
                $this->openPassword($request),
            );

            Mail::to($to)
                ->bcc($bcc)
                ->send(new ReportPdfMail(
                    subjectLine: $subject,
                    pdf: $pdf->output(),
                    filename: $filename,
                    bodyView: $bodyView,
                    bodyData: $bodyData,
                    extraAttachments: $extraAttachments,
                ));
        }

        $message = 'Report sent to ' . $recipients->implode(', ');

        // Answer XHR sends with JSON so the calculator page never reloads. A full
        // reload re-initialises the calculator JS to its defaults, which wiped the
        // user's inputs and made it impossible to email a second report without
        // re-entering everything.
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'recipients' => $recipients->all(),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Email failure response: JSON for XHR sends, redirect-back for the plain
     * form fallback (no JS).
     */
    private function emailFailure(Request $request, string $error): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $error], 422);
        }

        return back()->with('error', $error);
    }

    /**
     * Choose the email body + data for a report type. Only the Workforce/Budget
     * report uses the branded Cost to Protect cover; the rest fall back to the
     * short generic body. Figures are derived from the same fixed vendor-discount
     * the PDF uses, so the email matches the attachment.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function emailBodyFor(string $type, array $payload): array
    {
        if ($type === 'budget-calculator-preview') {
            // The whole point of the preview is that the figures are withheld —
            // the Cost to Protect cover email quotes them, so it must not be used.
            return ['emails.cost-to-protect-locked', [
                'reportNumber' => $payload['reportNumber'] ?? null,
                'datePrepared' => now()->format('F j, Y'),
            ]];
        }

        if ($type !== 'budget-calculator') {
            return ['emails.report-pdf', []];
        }

        // Same figures the attached PDF prints, from the same service, so the
        // covering email can never quote a different number than the estimate.
        $user = $payload['user'] ?? null;
        $estimate = app(CostToProtectEstimate::class)->build((array) data_get($payload, 'scenario', []), $user);
        $inHouse = (float) $estimate['totalAnnualInternal'];

        return ['emails.cost-to-protect', [
            // Greet the report's Contact (entered on the calculator) first; fall
            // back to the company, then the signed-in vendor's name.
            'clientName'     => $estimate['contact']['name'] ?: ($estimate['contact']['company'] ?: null),
            'propertyName'   => $estimate['contact']['site'],
            'reportNumber'   => $payload['reportNumber'] ?? null,
            'datePrepared'   => now()->format('F j, Y'),
            'inHouseCost'    => $inHouse > 0 ? $inHouse : null,
            'capitalRecovery' => $inHouse > 0 ? $estimate['annualCapitalRecovery'] : null,
            'paybackPeriod'  => $inHouse > 0 ? $estimate['paybackMonths'] . ' months' : null,
        ]];
    }

    /**
     * Password the recipient must enter to open the PDF, as typed by the vendor.
     * Blank/absent ⇒ null, and the file opens without one (previous behaviour).
     * The PDF format truncates passwords at 32 bytes, which the request rules cap.
     */
    private function openPassword(Request $request): ?string
    {
        $password = trim((string) $request->input('pdf_password', ''));

        return $password !== '' ? $password : null;
    }

    private function payloadForType(Request $request, ?string $type): ?array
    {
        if (! $type) {
            return null;
        }

        // Buyer report shares data with the base vendor report type
        $lookupType = match ($type) {
            'mobile-patrol-buyer' => 'mobile-patrol',
            // The allocation report and the locked preview both reuse the
            // Workforce calculator's stored data.
            'budget-calculator-allocation' => 'budget-calculator',
            'budget-calculator-preview' => 'budget-calculator',
            default => $type,
        };

        $payload = session('report_payload');
        if ($payload && ($payload['type'] ?? null) === $lookupType) {
            return $this->withIdentity($request, array_merge($payload, ['type' => $type]));
        }

        $user = $request->user();
        if (! $user) {
            return null;
        }

        /** @var CalculatorState|null $state */
        $state = $user->calculatorStates()
            ->where('calculator_type', $lookupType)
            ->latest('last_ran_at')
            ->first();

        if (! $state) {
            return null;
        }

        return $this->withIdentity($request, [
            'type' => $type,
            'scenario' => $state->scenario ?? [],
            'result' => $state->result ?? [],
            'reportId' => $state->id,
        ]);
    }

    /**
     * Stamp vendor identity + a unique report number on every payload so the PDF
     * can render the "Prepared for / Report #" header consistently and so vendors
     * can pass the doc to a buyer with a traceable identifier.
     *
     * Report # format: GASQ-{YYYYMMDD}-{HHMMSS}-V{vendor_id}
     */
    private function withIdentity(Request $request, array $payload): array
    {
        $user = $request->user();
        $vendorId = (int) ($user?->id ?? 0);
        $reportNumber = 'GASQ-' . now()->format('Ymd-His') . '-V' . $vendorId;
        $preparedFor = trim(($user?->name ?? '') . ($user?->company ? ' - ' . strtoupper($user->company) : ''));

        return array_merge($payload, [
            'user' => $user,
            'vendorId' => $vendorId,
            'reportNumber' => $reportNumber,
            'preparedFor' => $preparedFor !== '' ? $preparedFor : null,
        ]);
    }
}
