<?php

namespace Tests\Feature;

use App\Models\CalculatorState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorStatePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_payload_store_persists_latest_calculator_state_to_database(): void
    {
        $user = User::factory()->create([
            'phone' => null,
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('backend.report-payload.store'), [
            'type' => 'mobile-patrol-comparison',
            'scenario' => [
                'a' => ['hoursPerDay' => 24],
                'b' => ['hoursPerDay' => 12],
            ],
            'result' => [
                'scenario_a_annual' => 1000,
                'scenario_b_annual' => 800,
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('calculator_states', [
            'user_id' => $user->id,
            'calculator_type' => 'mobile-patrol-comparison',
        ]);

        $state = CalculatorState::query()
            ->where('user_id', $user->id)
            ->where('calculator_type', 'mobile-patrol-comparison')
            ->first();

        $this->assertNotNull($state);
        $this->assertSame(24, data_get($state?->scenario, 'a.hoursPerDay'));
        $this->assertSame(800, data_get($state?->result, 'scenario_b_annual'));
        $this->assertSame('mobile-patrol-comparison', session('report_payload.type'));
    }
}
