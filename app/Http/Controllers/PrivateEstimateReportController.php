<?php

namespace App\Http\Controllers;

use App\Models\PrivateEstimate;
use App\Services\PrivateEstimate\EstimateComparisonPdf;
use App\Services\PrivateEstimate\EstimateComparisonReport;
use App\Services\PrivateEstimate\PrivateEstimateService;
use App\Support\PrivateEstimate as Flow;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * The vendor's view of the buyer-facing comparison report: preview it on screen,
 * or download the PDF that goes to the buyer.
 *
 * The buyer reaches the same figures through their invitation portal; they never
 * come through here, because this route is authorised by the vendor account.
 */
class PrivateEstimateReportController extends Controller
{
    public function __construct(
        private EstimateComparisonReport $report,
        private EstimateComparisonPdf $pdf,
        private PrivateEstimateService $estimates,
    ) {}

    /** On-screen preview of exactly what the buyer will see. */
    public function show(Request $request, PrivateEstimate $estimate)
    {
        $this->authorizeVendor($request, $estimate);

        return view('private-estimates.vendor.comparison', [
            'estimate' => $estimate,
            'r' => $this->report->build($estimate),
        ]);
    }

    /**
     * The PDF. A password may be supplied, in which case the buyer must enter it
     * to open the file: the same locked-document pattern the rest of the
     * platform uses, with the vendor holding the key.
     */
    public function download(Request $request, PrivateEstimate $estimate): Response
    {
        $this->authorizeVendor($request, $estimate);

        $data = $request->validate([
            'pdf_password' => ['nullable', 'string', 'min:8', 'max:32', 'regex:/^[\x20-\x7E]+$/'],
        ]);

        $pdf = $this->pdf->render($estimate, $data['pdf_password'] ?? null);

        // Render once and send those exact bytes: a second render would rewrite
        // the permission mask while keeping the first password verifier, leaving
        // a file that its own password no longer opens.
        $bytes = $pdf->output();

        $this->estimates->log($estimate, Flow::EVENT_PDF_GENERATED, $request->user(), 'vendor', [
            'document' => 'comparison_report',
            'scope_version' => $estimate->current_scope_version,
            'pdf_sha256' => hash('sha256', $bytes),
            'password_protected' => ! empty($data['pdf_password']),
        ]);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $this->pdf->filename($estimate),
            ),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Only the vendor who owns the estimate, and only once it carries a
     * calculated rate: there is no comparison to show before that.
     */
    private function authorizeVendor(Request $request, PrivateEstimate $estimate): void
    {
        abort_unless($estimate->vendor_id === $request->user()->id, 404);
        abort_unless(
            $this->report->ready($estimate),
            422,
            'This estimate has no calculated rate yet, so there is nothing to compare.',
        );
    }
}
