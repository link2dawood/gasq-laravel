<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ReportPdfMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SpaCalculatorMailController extends Controller
{
    /** @deprecated Prefer calculatorPdf; kept for older SPA paths */
    public function economicJustification(Request $request): JsonResponse
    {
        return $this->calculatorPdf($request);
    }

    /**
     * Email a calculator PDF generated client-side (embedded SPA).
     */
    public function calculatorPdf(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['email'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'pdf_base64' => ['required', 'string'],
            'filename' => ['nullable', 'string', 'max:255'],
        ]);

        $binary = base64_decode(
            Str::after($validated['pdf_base64'], ',') ?: $validated['pdf_base64'],
            true
        );

        if ($binary === false || strlen($binary) < 100) {
            return response()->json(['ok' => false, 'message' => 'Invalid PDF payload'], 422);
        }

        $filename = $validated['filename'] ?? 'report-Calculator.pdf';
        $subject = $validated['subject'] ?? 'GASQ Calculator Report';
        $cc = $validated['cc'] ?? [];

        Mail::to($validated['to'])
            ->when($cc !== [], fn ($m) => $m->cc($cc))
            ->send(new ReportPdfMail(
                subjectLine: $subject,
                pdf: $binary,
                filename: $filename,
            ));

        return response()->json(['ok' => true]);
    }
}
