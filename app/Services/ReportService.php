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
            'mobile-patrol-comparison' => 'pdf.mobile-patrol-comparison',
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };

        $data = array_merge($payload, [
            'generatedAt' => now()->format('M j, Y g:i A'),
        ]);

        return $this->pdf()->loadView($view, $data)->setPaper('a4')->setWarnings(false);
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
     */
    public function filenameForCalculator(string $type): string
    {
        $slug = str_replace(' ', '-', $type);
        return "GASQ-{$slug}-" . now()->format('Y-m-d-His') . '.pdf';
    }
}
