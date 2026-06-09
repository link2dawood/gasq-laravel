<?php

namespace App\Http\Controllers;

use App\Mail\ReportPdfMail;
use App\Models\CalculatorState;
use App\Models\Transaction;
use App\Services\ReportService;
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
        private ReportService $report
    ) {}

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
        ]);

        // Accept one or more recipients separated by comma, semicolon, space or newline.
        $recipients = collect(preg_split('/[,;\s]+/', (string) $request->input('email'), -1, PREG_SPLIT_NO_EMPTY))
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

        $pdf = $this->report->calculatorPdf($type, $payload);
        $filename = $this->report->filenameForCalculator($type, $request->user());
        $pdfData = $pdf->output();
        $subject = 'Your GASQ Calculator Report – ' . str_replace('-', ' ', ucfirst($type));

        // The Workforce/Budget report ships the branded "Cost to Protect" cover
        // email; everything else uses the short generic body.
        [$bodyView, $bodyData] = $this->emailBodyFor($type, $payload);
        if ($bodyView === 'emails.cost-to-protect') {
            $subject = 'Your GASQ Cost to Protect™ Appraisal Report';
        }

        // Send each recipient their own copy (so they don't see each other), and
        // BCC the GASQ inbox so we keep a copy of every estimate sent.
        foreach ($recipients as $to) {
            Mail::to($to)
                ->bcc(self::ESTIMATE_BCC)
                ->send(new ReportPdfMail(
                    subjectLine: $subject,
                    pdf: $pdfData,
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

        // Mirror pdf.workforce-bill-rate-breakdown: vendor TCO is 70% of internal,
        // so capital recovery is the remaining 30% and payback is a fixed 8.4 mo.
        $vendorDiscountFactor = 0.70;

        $meta = (array) data_get($payload, 'scenario.meta', []);
        $contact = (array) data_get($payload, 'scenario.contact', []);
        $inHouse = (float) ($meta['annualBudget'] ?? 0);

        return ['emails.cost-to-protect', [
            'clientName'     => trim((string) ($contact['contactName'] ?? $contact['companyName'] ?? '')) ?: null,
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
