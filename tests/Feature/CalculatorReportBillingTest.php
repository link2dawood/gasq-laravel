<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Calculators with a lot of inputs recalculate free and cost credits once, at
 * the report. Charging per compute made ordinary editing expensive: a vendor
 * working through the Bill Rate Breakdown's sixty-odd inputs paid for each one.
 */
class CalculatorReportBillingTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(int $credits = 500): User
    {
        $user = User::factory()->create([
            'user_type' => 'vendor',
            'nda_accepted_at' => now(),
            'phone_verified' => true,
        ]);
        app(WalletService::class)->addTokens($user, $credits, 'test', 'test credits');

        return $user->fresh();
    }

    private function balance(User $user): int
    {
        return app(WalletService::class)->getBalance($user);
    }

    public function test_bill_rate_recalculation_is_free(): void
    {
        $user = $this->vendor();
        $before = $this->balance($user);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->postJson(route('backend.standalone.v24.compute', ['type' => 'bill-rate-analysis']), [
                    'version' => 'v24',
                    'scenario' => ['meta' => ['brb' => ['profit' => ['marginPct' => 15 + $i]]]],
                ])
                ->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('credits_spent', 0);
        }

        $this->assertSame($before, $this->balance($user), 'Recalculating must not cost credits.');
    }

    public function test_the_response_still_reports_the_current_balance(): void
    {
        $user = $this->vendor(320);

        $this->actingAs($user)
            ->postJson(route('backend.standalone.v24.compute', ['type' => 'bill-rate-analysis']), [
                'version' => 'v24',
                'scenario' => ['meta' => ['brb' => []]],
            ])
            ->assertOk()
            ->assertJsonPath('credits_remaining', 320);
    }

    public function test_calculators_not_billed_at_the_report_still_charge_per_run(): void
    {
        $user = $this->vendor();
        $before = $this->balance($user);
        $perRun = (int) config('credits.calculator_per_run');

        $this->actingAs($user)
            ->postJson(route('backend.standalone.v24.compute', ['type' => 'economic-justification']), [
                'version' => 'v24',
                'scenario' => ['meta' => []],
            ])
            ->assertOk()
            ->assertJsonPath('credits_spent', $perRun);

        $this->assertSame($before - $perRun, $this->balance($user));
    }

    public function test_the_billed_list_is_configured_in_one_place(): void
    {
        $types = (array) config('credits.report_billed_types');

        $this->assertContains('bill-rate-analysis', $types);
        $this->assertContains('budget-calculator', $types);
        $this->assertContains('budget-calculator-allocation', $types);
    }
}
