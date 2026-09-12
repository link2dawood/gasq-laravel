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
            'reportNumber' => 'GASQ-20260912-010135-V1',
        ];
    }

    private function buyerEdition(): string
    {
        return view('pdf.cost-to-protect-estimate', array_merge($this->payload(), [
            'reportType' => 'budget-calculator-preview',
            'masked' => true,
        ]))->render();
    }

    public function test_buyer_edition_shows_every_buyer_internal_figure(): void
    {
        $html = $this->buyerEdition();

        $this->assertStringContainsString('$538,769', $html, 'buyer internal annual cost');
        $this->assertStringContainsString('$107.93', $html, 'buyer internal hourly rate');
        $this->assertStringContainsString('4 FTEs', $html, 'total staff required');
        $this->assertStringContainsString('4,992', $html, 'annual coverage hours');
        $this->assertStringContainsString('96', $html, 'weekly hours');
        $this->assertStringContainsString('416', $html, 'monthly hours');
    }

    public function test_buyer_edition_withholds_every_vendor_and_savings_figure(): void
    {
        $html = $this->buyerEdition();

        $this->assertStringNotContainsString('$377,138', $html, 'vendor annual cost');
        $this->assertStringNotContainsString('$75.55', $html, 'vendor hourly rate');
        $this->assertStringNotContainsString('$113.32', $html, 'vendor overtime rate');
        $this->assertStringNotContainsString('$161,630', $html, 'capital recovered');
        $this->assertStringNotContainsString('8.4 months', $html, 'payback period');
        $this->assertStringContainsString('•', $html, 'withheld figures are veiled, not removed');
    }

    public function test_buyer_edition_charts_drop_the_vendor_series(): void
    {
        $html = $this->buyerEdition();

        // A vendor bar drawn to scale would reveal the saving even unlabelled, so
        // the buyer edition must not paint one at all.
        $this->assertStringNotContainsString('Vendor Outsourcing<br>Cost to Protect', $html);
        // Bars are tall filled cells; the brand bar's 3px accent rule is not one.
        $this->assertDoesNotMatchRegularExpression('/height:\d{2,}px; background:#ef6c1f/', $html, 'no orange bar drawn');
    }

    public function test_buyer_edition_says_what_it_is_and_keeps_the_analysis(): void
    {
        $html = $this->buyerEdition();

        $this->assertStringContainsString('BUYER EDITION', $html);
        $this->assertStringContainsString('Buyer Edition — In-House Figures Only', $html);
        $this->assertStringContainsString('Buyer Internal Cost to Protect', $html);
        $this->assertStringContainsString('Payback &amp; Recovery Period', $html);
        $this->assertStringContainsString('Page 4 of 4', $html);
    }

    public function test_the_full_report_still_shows_everything(): void
    {
        $html = view('pdf.cost-to-protect-estimate', array_merge($this->payload(), [
            'reportType' => 'budget-calculator',
        ]))->render();

        $this->assertStringContainsString('$538,769', $html);
        $this->assertStringContainsString('$377,138', $html);
        $this->assertStringContainsString('$75.55', $html);
        $this->assertStringNotContainsString('•••', $html);
        $this->assertStringNotContainsString('BUYER EDITION', $html);
        $this->assertStringContainsString('Vendor — Full Report', $html);
    }

    public function test_an_open_password_makes_the_pdf_unreadable_without_it(): void
    {
        $locked = app(ReportService::class)
            ->calculatorPdf('budget-calculator', $this->payload(), 'Northgate2026')
            ->output();

        $this->assertStringStartsWith('%PDF-', $locked);
        $this->assertStringContainsString('/Encrypt', $locked);
        $this->assertStringNotContainsString('Buyer Internal Cost to Protect', $locked);
    }

    public function test_the_buyer_edition_is_never_password_protected(): void
    {
        // It is the free version the buyer may forward; it must open for anyone.
        $preview = app(ReportService::class)
            ->calculatorPdf('budget-calculator-preview', $this->payload(), 'IgnoreMe')
            ->output();

        $this->assertStringStartsWith('%PDF-', $preview);
    }

    public function test_report_filenames_distinguish_the_two_documents(): void
    {
        $service = app(ReportService::class);

        $this->assertStringContainsString('Cost-to-Protect-Estimate', $service->filenameForCalculator('budget-calculator'));
        $this->assertStringContainsString('BUYER-EDITION', $service->filenameForCalculator('budget-calculator-preview'));
    }
}
