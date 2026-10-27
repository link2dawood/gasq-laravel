<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\V24\MainMenu\MainMenuComputeService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * Get the PDF wrapper instance (barryvdh/laravel-dompdf).
     */
    private function pdf(): PDF
    {
        return app('dompdf.wrapper');
    }

    /**
     * Generate PDF for a credit purchase receipt.
     */
    public function receiptPdf(Transaction $transaction): PDF
    {
        $transaction->loadMissing('user');

        return $this->pdf()->loadView('pdf.receipt', [
            'transaction' => $transaction,
            'user' => $transaction->user,
            'generatedAt' => now()->format('M j, Y g:i A'),
        ])->setPaper('a4')->setWarnings(false);
    }

    /**
     * Generate PDF for a calculator report by type.
     *
     * @param  array<string, mixed>  $payload  Must contain 'result' and optionally 'title', 'inputs', etc.
     * @param  string|null  $openPassword  When set, the recipient must enter this
     *                                     password to OPEN the file. The vendor chooses it per send and passes
     *                                     it to the buyer out of band.
     */
    public function calculatorPdf(string $type, array $payload, ?string $openPassword = null): PDF
    {
        if ($type === 'buyer-appraisal') {
            validator(['pdf_password' => $openPassword], ['pdf_password' => ['required', 'string', 'min:8', 'max:32', 'regex:/^[\x20-\x7E]+$/']])->validate();
        }
        if ($type === 'vendor-weighted-rates') {
            if (strlen(trim((string) $openPassword)) < 8 || strlen((string) $openPassword) > 32 || ! preg_match('/^[\x20-\x7E]+$/', (string) $openPassword)) {
                throw ValidationException::withMessages(['pdf_password' => ['Use an 8–32 character PDF password with standard letters, numbers, or symbols.']]);
            }
            $payload = app(VendorWeightedRateReport::class)->prepare($payload);
        }
        if ($type === 'main-menu' && data_get($payload, 'scenario.meta.vendorWeightedRates')) {
            $payload['scenario'] = app(VendorWeightedRateApplication::class)->publicScenario($payload['scenario']);
            $payload['result'] = app(MainMenuComputeService::class)->compute($payload['scenario']);
        }
        if ($type === 'main-menu' && data_get($payload, 'scenario.meta.roiCostBasis') === 'budget') {
            if (($payload['user'] ?? null) instanceof User) {
                $payload['scenario'] = app(BudgetSummaryService::class)->applyToMainMenu($payload['user'], $payload['scenario']);
            }
            $payload['result'] = app(MainMenuComputeService::class)->compute($payload['scenario']);
        }
        $view = match ($type) {
            'instant-estimator' => 'pdf.instant-estimator',
            'main-menu' => 'pdf.main-menu',
            'vendor-weighted-rates' => 'pdf.vendor-weighted-rates',
            'contract-analysis' => 'pdf.contract-analysis',
            'security-billing' => 'pdf.security-billing',
            'mobile-patrol' => 'pdf.mobile-patrol',
            // Buyer report = the full vendor report with vehicle-mechanics and
            // profit (return-on-sales) detail rows hidden (see $isBuyer in view).
            'mobile-patrol-buyer' => 'pdf.mobile-patrol',
            'mobile-patrol-comparison' => 'pdf.mobile-patrol-comparison',
            'mobile-patrol-hit-calculator' => 'pdf.mobile-patrol-hit-calculator',
            // Know Before You Bid Calculator → two documents off the same data:
            //   'budget-calculator'            → the master Cost to Protect estimate
            //     dashboard, the paid deliverable a vendor sends to a buyer;
            //   'budget-calculator-allocation' → Workforce-to-Post allocation totals
            //     and line-item breakdown.
            'budget-calculator', 'buyer-appraisal' => 'pdf.cost-to-protect-estimate',
            // Same document as the buyer's free edition: their in-house figures in
            // full, the vendor's cost and the savings withheld.
            'budget-calculator-preview' => 'pdf.cost-to-protect-estimate',
            'budget-calculator-allocation' => 'pdf.workforce-bill-rate-breakdown',
            // Generic standalone calculators (server-rendered PDF from latest session payload)
            'mobile-patrol-analysis',
            'gasq-tco-calculator',
            'government-contract-calculator',
            'economic-justification',
            'bill-rate-analysis',
            'workforce-appraisal-report',
            'buyer-fit-index',
            'gasq-direct-labor-build-up',
            'gasq-additional-cost-stack' => 'pdf.standalone',
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };

        $data = array_merge($payload, [
            'generatedAt' => now()->format('M j, Y g:i A'),
            'reportType' => $type,
            'isBuyer' => $type === 'mobile-patrol-buyer',
            'reportScope' => $type === 'budget-calculator-allocation' ? 'allocation' : 'main',
            'masked' => $type === 'budget-calculator-preview',
        ]);

        $pdf = $this->pdf()->loadView($view, $data)
            ->setPaper($type === 'vendor-weighted-rates' ? 'legal' : 'a4', $type === 'vendor-weighted-rates' ? 'landscape' : 'portrait')
            ->setWarnings(false);
        if ($type === 'vendor-weighted-rates') {
            $pdf->render();
            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->getCanvas();
            $currency = $payload['reportCurrency'];
            $fxNotice = $currency['code'] === 'USD' ? 'Currency: USD (base currency)'
                : 'Currency: '.$currency['code'].' | 1 USD = '.number_format($currency['rate'], 6).' '.$currency['code'].' | '.$currency['source'].' reference: '.$currency['asOf'];
            $canvas->page_text(30, $canvas->get_height() - 33, $fxNotice,
                $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.38, 0.46, 0.55]);
            $canvas->page_text(30, $canvas->get_height() - 21,
                'CONFIDENTIAL | VENDOR WEIGHTED RATE REPORT | '.($payload['reportNumber'] ?? 'GASQ').' | Page {PAGE_NUM} of {PAGE_COUNT}',
                $dompdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.38, 0.46, 0.55]);
        }

        // The buyer edition is meant to be forwarded freely: it carries the buyer's
        // own figures, and the vendor's are already withheld inside the document.
        return $this->restrictCopyAndPrint(
            $pdf,
            $type === 'budget-calculator-preview' ? null : $openPassword,
            in_array($type, ['vendor-weighted-rates', 'buyer-appraisal'], true),
        );
    }

    /**
     * Encrypt the report: optionally require a password to open it, and restrict
     * what a recipient may do once it is open.
     *
     * Printing is ALLOWED. Copying, modifying and annotating are denied. A buyer
     * who has paid for the estimate needs to be able to print it; the earlier
     * blanket print ban only produced a password prompt at the printer.
     *
     * Note: permissions are advisory — honoured by compliant readers (Acrobat,
     * Preview, Chrome), strippable by third-party tools, and never a defence
     * against a screenshot. The open password is the real lock: without it the
     * file's contents cannot be read at all.
     *
     * @param  string|null  $openPassword  Non-empty ⇒ the reader must enter it to
     *                                     open the document. Null/empty ⇒ opens freely, as before.
     */
    private function restrictCopyAndPrint(PDF $pdf, ?string $openPassword = null, bool $requireEncryption = false): PDF
    {
        // The canvas only exists after rendering, so render up front; the
        // wrapper sets its "rendered" flag, so download()/output() won't
        // re-render and drop the encryption we apply below.
        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();
        if (method_exists($canvas, 'get_cpdf')) {
            // The OWNER password is GASQ's master override — entering it in a PDF
            // reader lifts the restrictions below. Without one configured we fall
            // back to a random password, i.e. nobody can lift them.
            $ownerPassword = (string) (config('services.gasq.report_master_password') ?: Str::random(32));

            // PDF passwords are truncated to 32 bytes by the format itself; trim
            // rather than silently hand the recipient a password that will not work.
            $userPassword = mb_substr(trim((string) $openPassword), 0, 32);

            // An owner password identical to the user password would let the
            // recipient lift every restriction just by opening the file.
            if ($userPassword !== '' && $userPassword === $ownerPassword) {
                $ownerPassword = Str::random(32);
            }

            $canvas->get_cpdf()->setEncryption($userPassword, $ownerPassword, ['print']);
        } elseif ($requireEncryption) {
            throw new \RuntimeException('This PDF renderer does not support the required password protection.');
        }

        return $pdf;
    }

    /**
     * Suggested filename for a report type.
     */
    public function filenameForReceipt(Transaction $transaction): string
    {
        $date = $transaction->created_at->format('Y-m-d');

        return "GASQ-receipt-{$date}.pdf";
    }

    /**
     * Suggested filename for a calculator report.
     * Appends a vendor stamp when a user is supplied so files sent to buyers
     * carry a traceable origin (e.g. "GASQ-budget-calculator-2026-05-13-V7.pdf").
     */
    public function filenameForCalculator(string $type, ?User $user = null): string
    {
        // The master estimate goes to a buyer under its own name, not the
        // internal calculator slug.
        $slug = match ($type) {
            'budget-calculator' => 'Cost-to-Protect-Estimate',
            'budget-calculator-preview' => 'Cost-to-Protect-Estimate-BUYER-EDITION',
            'budget-calculator-allocation' => 'Workforce-to-Post-Allocation',
            'vendor-weighted-rates' => 'Vendor-Weighted-Rate-Dashboard',
            default => str_replace(' ', '-', $type),
        };
        $stamp = now()->format('Y-m-d-His');
        $vendorTag = $user ? '-V'.(int) $user->id : '';

        return "GASQ-{$slug}-{$stamp}{$vendorTag}.pdf";
    }
}
