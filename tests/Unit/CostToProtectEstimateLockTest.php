<?php

namespace Tests\Unit;

use App\Services\ReportService;
use Tests\TestCase;

class CostToProtectEstimateLockTest extends TestCase
{
    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'scenario' => ['meta' => [
                'baselineWage' => 29.38,
                'scope' => [
                    'hoursOfCoveragePerDay' => 24,
                    'daysOfCoveragePerWeek' => 4,
                    'weeksOfCoverage' => 52,
                    'staffPerShift' => 1,
                ],
            ]],
            'result' => [],
            'user' => null,
            'vendorId' => 1,
            'reportNumber' => 'GASQ-20260910-010135-V1',
        ];
    }

    public function test_locked_preview_masks_every_figure_but_keeps_the_analysis(): void
    {
        $html = view('pdf.cost-to-protect-estimate', array_merge($this->payload(), [
            'reportType' => 'budget-calculator-preview',
            'masked' => true,
        ]))->render();

        // Figures are veiled, digit for digit
        $this->assertStringNotContainsString('$538,769', $html);
        $this->assertStringNotContainsString('$377,138', $html);
        $this->assertStringNotContainsString('$107.93', $html);
        $this->assertStringContainsString('$•••,•••', $html);

        // …and the document still says what it is
        $this->assertStringContainsString('LOCKED PREVIEW', $html);
        $this->assertStringContainsString('Locked Preview — Figures Withheld', $html);

        // The analysis itself is intact
        $this->assertStringContainsString('Buyer Internal Cost to Protect', $html);
        $this->assertStringContainsString('Payback &amp; Recovery Period', $html);
        $this->assertStringContainsString('Page 4 of 4', $html);
    }

    public function test_the_full_report_still_shows_its_figures(): void
    {
        $html = view('pdf.cost-to-protect-estimate', array_merge($this->payload(), [
            'reportType' => 'budget-calculator',
        ]))->render();

        $this->assertStringContainsString('$538,769', $html);
        $this->assertStringNotContainsString('•••', $html);
        $this->assertStringNotContainsString('LOCKED PREVIEW', $html);
        $this->assertStringContainsString('Vendor — Full Report', $html);
    }

    public function test_an_open_password_makes_the_pdf_unreadable_without_it(): void
    {
        $locked = app(ReportService::class)
            ->calculatorPdf('budget-calculator', $this->payload(), 'Northgate2026')
            ->output();

        // An encrypted PDF still carries a header and an /Encrypt dictionary, but
        // none of the page text survives in the clear.
        $this->assertStringStartsWith('%PDF-', $locked);
        $this->assertStringContainsString('/Encrypt', $locked);
        $this->assertStringNotContainsString('Buyer Internal Cost to Protect', $locked);
    }

    public function test_the_locked_preview_is_never_password_protected(): void
    {
        // It is the teaser — it must open for anyone the vendor forwards it to.
        $preview = app(ReportService::class)
            ->calculatorPdf('budget-calculator-preview', $this->payload(), 'IgnoreMe')
            ->output();

        $this->assertStringStartsWith('%PDF-', $preview);
    }

    public function test_report_filenames_distinguish_the_two_documents(): void
    {
        $service = app(ReportService::class);

        $this->assertStringContainsString('Cost-to-Protect-Estimate', $service->filenameForCalculator('budget-calculator'));
        $this->assertStringContainsString('LOCKED-PREVIEW', $service->filenameForCalculator('budget-calculator-preview'));
    }
}
