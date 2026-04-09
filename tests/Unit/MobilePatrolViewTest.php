<?php

namespace Tests\Unit;

use Tests\TestCase;

class MobilePatrolViewTest extends TestCase
{
    public function test_mobile_patrol_view_uses_shared_input_and_results_workspace_layout(): void
    {
        $html = view('calculators.mobile-patrol')->render();

        $this->assertStringContainsString('Shared Inputs', $html);
        $this->assertStringContainsString('Patrol Model Controls', $html);
        $this->assertStringContainsString('Mobile Patrol Outputs', $html);
        $this->assertStringContainsString('The cost stack and billing summary below update from the shared patrol inputs on the left.', $html);
        $this->assertStringContainsString('Contact Information', $html);
        $this->assertStringContainsString('Cost Summary', $html);
    }
}
