<?php

namespace Tests\Unit;

use Tests\TestCase;

class CalculatorDirectoryViewTest extends TestCase
{
    public function test_calculator_directory_lists_the_full_calculator_suite(): void
    {
        $html = view('calculators.security-calculator')->render();

        $this->assertStringContainsString('All Calculators', $html);
        $this->assertStringContainsString('Master Inputs', $html);
        $this->assertStringContainsString('Main Menu Calculator', $html);
        $this->assertStringContainsString('Security Billing', $html);
        $this->assertStringContainsString('Bill Rate Analysis', $html);
        $this->assertStringContainsString('Budget Calculator', $html);
        $this->assertStringContainsString('Mobile Patrol Comparison', $html);
        $this->assertStringContainsString('Workforce Appraisal Report', $html);
        $this->assertStringContainsString('GASQ Direct Labor Build-Up', $html);
        $this->assertStringContainsString('GASQ Additional Cost Stack', $html);
        $this->assertStringContainsString('Open Bid Offer', $html);
        $this->assertStringNotContainsString('currently hidden', $html);
    }
}
