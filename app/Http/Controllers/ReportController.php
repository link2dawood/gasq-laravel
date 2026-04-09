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
        return $pdf->download($this->report->filenameForCalculator($type));
    }

    /**
     * Email calculator report PDF.
     */
    public function emailReport(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:instant-estimator,main-menu,contract-analysis,security-billing,mobile-patrol,mobile-patrol-comparison,mobile-patrol-hit-calculator,mobile-patrol-analysis,gasq-tco-calculator,government-contract-calculator,budget-calculator,economic-justification,bill-rate-analysis,workforce-appraisal-report,buyer-fit-index,gasq-direct-labor-build-up,gasq-additional-cost-stack',
            'email' => 'required|email',
        ]);

        $type = $request->input('type');
        $payload = $this->payloadForType($request, $type);
        if (! $payload) {
            return back()->with('error', 'No report data available. Run the calculator again and use Email report.');
        }

        $pdf = $this->report->calculatorPdf($type, $payload);
        $filename = $this->report->filenameForCalculator($type);

        Mail::to($request->input('email'))->send(new ReportPdfMail(
            subjectLine: 'Your GASQ Calculator Report – ' . str_replace('-', ' ', ucfirst($type)),
            pdf: $pdf->output(),
            filename: $filename,
        ));

        return back()->with('success', 'Report sent to ' . $request->input('email'));
    }

    private function payloadForType(Request $request, ?string $type): ?array
    {
        if (! $type) {
            return null;
        }

        $payload = session('report_payload');
        if ($payload && ($payload['type'] ?? null) === $type) {
            return $payload;
        }

        $user = $request->user();
        if (! $user) {
            return null;
        }

        /** @var CalculatorState|null $state */
        $state = $user->calculatorStates()
            ->where('calculator_type', $type)
            ->latest('last_ran_at')
            ->first();

        if (! $state) {
            return null;
        }

        return [
            'type' => $state->calculator_type,
            'scenario' => $state->scenario ?? [],
            'result' => $state->result ?? [],
        ];
    }
}
