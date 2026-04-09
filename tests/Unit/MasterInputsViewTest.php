<?php

namespace Tests\Unit;

use Tests\TestCase;

class MasterInputsViewTest extends TestCase
{
    public function test_master_inputs_uses_left_controls_right_results_layout_with_plain_number_inputs(): void
    {
        $html = view('pages.master-inputs', [
            'inputs' => [],
            'isComplete' => false,
        ])->render();

        $this->assertStringContainsString('Master Input Controls', $html);
        $this->assertStringContainsString('Live Master Input Summary', $html);
        $this->assertStringContainsString('id="mi_results_tbody"', $html);
        $this->assertStringContainsString('id="mi_stat_loadedHourly"', $html);
        $this->assertStringContainsString('id="mi_stat_burdenPct"', $html);
        $this->assertStringContainsString('id="mi_stat_supportPct"', $html);
        $this->assertStringContainsString('id="mi_stat_fleetMiles"', $html);
        $this->assertStringContainsString('mi-input-stack', $html);
        $this->assertStringNotContainsString('mi-input-group', $html);
        $this->assertStringNotContainsString('input-group-text', $html);
        $this->assertStringContainsString('type="number"', $html);
    }
}
