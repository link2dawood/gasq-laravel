<?php

namespace App\Services\PrivateEstimate;

use App\Models\PrivateEstimate;
use App\Services\ReportService;
use Barryvdh\DomPDF\PDF;

/**
 * Renders the private estimate's side-by-side comparison report.
 *
 * Encryption goes through ReportService so this document inherits the same
 * rules as every other GASQ report: printing allowed, copying and modifying
 * denied, and an optional open password that the vendor releases to the buyer.
 */
class EstimateComparisonPdf
{
    public function __construct(
        private EstimateComparisonReport $report,
        private ReportService $reports,
    ) {}

    /**
     * @param  string|null  $openPassword  Non-empty ⇒ the buyer must enter it to open the file.
     *
     * IMPORTANT: output()/download()/stream() may be called only once on the
     * returned instance. See ReportService::restrictCopyAndPrint().
     */
    public function render(PrivateEstimate $estimate, ?string $openPassword = null): PDF
    {
        $estimate->loadMissing('vendor');

        $pdf = app('dompdf.wrapper')
            ->loadView('pdf.private-estimate-comparison', ['r' => $this->report->build($estimate)])
            ->setPaper('a4')
            ->setWarnings(false);

        return $this->reports->restrictCopyAndPrint($pdf, $openPassword);
    }

    /** e.g. GASQ-Cost-Comparison-PE-261117-0003.pdf */
    public function filename(PrivateEstimate $estimate): string
    {
        return 'GASQ-Cost-Comparison-'.$estimate->public_id.'.pdf';
    }
}
