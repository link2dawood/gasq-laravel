<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;

class ReportService
{
    /**
     * Get the PDF wrapper instance (barryvdh/laravel-dompdf).
     */
    private function pdf(): \Barryvdh\DomPDF\PDF
    {
        return app('dompdf.wrapper');
    }

    /**
     * Generate PDF for a credit purchase receipt.
     */
    public function receiptPdf(Transaction $transaction): \Barryvdh\DomPDF\PDF
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
     */
    public function calculatorPdf(string $type, array $payload): \Barryvdh\DomPDF\PDF
    {
        $view = match ($type) {
            'instant-estimator' => 'pdf.instant-estimator',
            'main-menu' => 'pdf.main-menu',
            'contract-analysis' => 'pdf.contract-analysis',
            'security-billing' => 'pdf.security-billing',
            'mobile-patrol' => 'pdf.mobile-patrol',
            // Buyer report = the full vendor report with vehicle-mechanics and
            // profit (return-on-sales) detail rows hidden (see $isBuyer in view).
            'mobile-patrol-buyer' => 'pdf.mobile-patrol',
            'mobile-patrol-comparison' => 'pdf.mobile-patrol-comparison',
            'mobile-patrol-hit-calculator' => 'pdf.mobile-patrol-hit-calculator',
            // Workforce Absorbed Rate Calculator → branded 3-page Workforce-to-Post Bill Rate Breakdown report.
            'budget-calculator' => 'pdf.workforce-bill-rate-breakdown',
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
        ]);

        $pdf = $this->pdf()->loadView($view, $data)->setPaper('a4')->setWarnings(false);

        return $this->restrictCopyAndPrint($pdf);
    }

    /**
     * Apply PDF permission flags that disable copying text and printing.
     *
     * Note: these are advisory restrictions honoured by compliant readers
     * (Acrobat, Preview, Chrome). They are a deterrent, not true security —
     * they can be stripped by third-party tools and never stop screenshots.
     * A random owner password locks the permissions; recipients still open
     * the file without any password (empty user password).
     */
    private function restrictCopyAndPrint(\Barryvdh\DomPDF\PDF $pdf): \Barryvdh\DomPDF\PDF
    {
        // The canvas only exists after rendering, so render up front; the
        // wrapper sets its "rendered" flag, so download()/output() won't
        // re-render and drop the encryption we apply below.
        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();
        if (method_exists($canvas, 'get_cpdf')) {
            // Empty permissions array grants nothing beyond viewing:
            // print, copy, modify and annotate are all denied for recipients
            // (they open with no password). The OWNER password is the master
            // override — anyone who enters it in a PDF reader can print/copy.
            // If no master password is configured, fall back to a random one
            // (fully locked, no override).
            $ownerPassword = (string) (config('services.gasq.report_master_password') ?: \Illuminate\Support\Str::random(32));
            $canvas->get_cpdf()->setEncryption('', $ownerPassword, []);
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
        $slug = str_replace(' ', '-', $type);
        $stamp = now()->format('Y-m-d-His');
        $vendorTag = $user ? '-V' . (int) $user->id : '';
        return "GASQ-{$slug}-{$stamp}{$vendorTag}.pdf";
    }
}
