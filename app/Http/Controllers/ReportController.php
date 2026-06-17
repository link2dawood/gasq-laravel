<?php

namespace App\Http\Controllers;

use App\Mail\ReportPdfMail;
use App\Models\CalculatorState;
use App\Models\Transaction;
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
    private const REPORT_BILLED_TYPES = ['budget-calculator'];

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

        $pdf = $this->report->calculatorPdf($type, $payload);
        return $pdf->download($this->report->filenameForCalculator($type, $request->user()));
    }

    /**
     * Email calculator report PDF.
     */
    public function emailReport(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:instant-estimator,main-menu,contract-analysis,security-billing,mobile-patrol,mobile-patrol-buyer,mobile-patrol-comparison,mobile-patrol-hit-calculator,mobile-patrol-analysis,gasq-tco-calculator,government-contract-calculator,budget-calculator,economic-justification,bill-rate-analysis,workforce-appraisal-report,buyer-fit-index,gasq-direct-labor-build-up,gasq-additional-cost-stack',
            'email' => 'required|string',
            'email2' => 'nullable|string',
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
            return back()->with('error', $invalid->isNotEmpty()
                ? 'These email addresses look invalid: ' . $invalid->implode(', ')
                : 'Enter at least one valid email address.');
        }

        $type = $request->input('type');
        $payload = $this->payloadForType($request, $type);
        if (! $payload) {
            return back()->with('error', 'No report data available. Run the calculator again and use Email report.');
        }

        if ($charge = $this->chargeForReport($request, $type, $payload)) {
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

        // BCC the GASQ inbox (record of each send) and the HubSpot "log to CRM"
        // address (auto-logs the report onto the contact's HubSpot timeline).
        $bcc = array_values(array_filter([self::ESTIMATE_BCC, config('services.hubspot.bcc')]));

        // Send each recipient their own copy (so they don't see each other),
        // stamped "Prepared exclusively for <their email>" so any forwarded copy
        // is traceable.
        foreach ($recipients as $to) {
            $pdf = $this->report->calculatorPdf($type, array_merge($payload, ['preparedForEmail' => $to]));

            Mail::to($to)
                ->bcc($bcc)
                ->send(new ReportPdfMail(
                    subjectLine: $subject,
                    pdf: $pdf->output(),
                    filename: $filename,
                    bodyView: $bodyView,
                    bodyData: $bodyData,
                ));
        }

        return back()->with('success', 'Report sent to ' . $recipients->implode(', '));
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
        if ($type !== 'budget-calculator') {
            return ['emails.report-pdf', []];
        }

        // Shared with pdf.workforce-bill-rate-breakdown: vendor TCO is this fraction
        // of internal TCO, so capital recovery is the remainder and payback is
        // (factor * 12) months. Reading the same config keeps email + PDF in sync.
        $vendorDiscountFactor = (float) config('budget_calculator.vendor_discount_factor', 0.70);

        $meta = (array) data_get($payload, 'scenario.meta', []);
        $contact = (array) data_get($payload, 'scenario.meta.contact', []);
        $user = $payload['user'] ?? null;
        $inHouse = (float) ($meta['annualBudget'] ?? 0);

        return ['emails.cost-to-protect', [
            // Greet the report's Contact (entered on the calculator) first; fall
            // back to the company, then the signed-in vendor's name.
            'clientName'     => trim((string) ($contact['contactName'] ?? $contact['companyName'] ?? $user?->name ?? '')) ?: null,
            'propertyName'   => trim((string) ($meta['siteName'] ?? $contact['siteName'] ?? '')) ?: null,
            'reportNumber'   => $payload['reportNumber'] ?? null,
            'datePrepared'   => now()->format('F j, Y'),
            'inHouseCost'    => $inHouse > 0 ? $inHouse : null,
            'capitalRecovery' => $inHouse > 0 ? $inHouse * (1 - $vendorDiscountFactor) : null,
            'paybackPeriod'  => $inHouse > 0 ? round($vendorDiscountFactor * 12, 1) . ' months' : null,
        ]];
    }

    private function payloadForType(Request $request, ?string $type): ?array
    {
        if (! $type) {
            return null;
        }

        // Buyer report shares data with the base vendor report type
        $lookupType = $type === 'mobile-patrol-buyer' ? 'mobile-patrol' : $type;

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
