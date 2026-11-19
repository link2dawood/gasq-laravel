<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorLeadEstimate;
use App\Services\CalculatorStateStore;
use App\Services\ReportService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Know Before You Bid dashboard attached to a vendor estimate.
 *
 * Two editions off one document: the vendor's own download carries every figure,
 * the buyer's emailed copy withholds the vendor cost, the capital recovered and
 * the payback period. The masking is what stops the estimate handing a buyer the
 * vendor's margin, so it is asserted figure by figure rather than by a flag.
 */
class EstimateBudgetDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function vendor(): User
    {
        return User::factory()->create(['user_type' => 'vendor', 'nda_accepted_at' => now(), 'currency' => 'USD']);
    }

    private function lead(User $vendor): VendorBuyerLead
    {
        return VendorBuyerLead::create(['vendor_id' => $vendor->id, 'contact_name' => 'Avery Buyer', 'email' => 'avery@example.test', 'phone' => '+14045550123', 'site_address' => '100 Main Street', 'service_type' => 'Guarding', 'scope' => 'Lobby security on weekdays.', 'weekly_hours' => 80]);
    }

    private function estimate(User $vendor, array $changes = []): VendorLeadEstimate
    {
        $lead = $this->lead($vendor);
        $this->actingAs($vendor)->post(route('vendor-estimates.store', $lead), array_merge([
            'title' => 'Lobby service estimate', 'scope' => 'Two officers on weekdays.',
            'hourly_rate' => 25.50, 'weekly_hours' => 80, 'weeks_per_year' => 52, 'one_time_cost' => 500,
            'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'draft',
        ], $changes))->assertRedirect();

        return $lead->estimates()->firstOrFail();
    }

    /**
     * The scenario behind the figures this test asserts: a 24/7-style post, four
     * days a week, all year, on a $29.38 baseline wage. The same scenario the
     * master dashboard's own lock test uses, so both documents are checked
     * against one agreed set of numbers.
     */
    private function saveCalculatorScenario(User $vendor, float $baselineWage = 29.38): void
    {
        app(CalculatorStateStore::class)->store($vendor, 'budget-calculator', ['meta' => [
            'baselineWage' => $baselineWage,
            'scope' => ['hoursOfCoveragePerDay' => 24, 'daysOfCoveragePerWeek' => 4, 'weeksOfCoverage' => 52, 'staffPerShift' => 1],
        ]], []);
    }

    private function render(VendorLeadEstimate $estimate, bool $masked): string
    {
        return view('pdf.vendor-lead-estimate', [
            'estimate' => $estimate->load('vendor'),
            'user' => $estimate->vendor,
            'masked' => $masked,
            'reportType' => $masked ? 'vendor-lead-estimate-buyer' : 'vendor-lead-estimate',
            'generatedAt' => now()->format('M j, Y g:i A'),
        ])->render();
    }

    public function test_filling_from_the_calculator_sets_the_pricing_and_attaches_the_dashboard(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor);

        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))
            ->assertOk()
            ->assertSee('Fill from my Know Before You Bid figures')
            ->assertSee('from=estimate&amp;estimate='.$estimate->id, false);

        $this->post(route('vendor-estimates.budget.attach', $estimate))->assertRedirect();
        $estimate->refresh();

        // $75.55 vendor cost to protect hourly rate, 96 hours of coverage a week.
        $this->assertEquals(75.55, (float) $estimate->hourly_rate);
        $this->assertEquals(96, (float) $estimate->weekly_hours);
        $this->assertSame(52, $estimate->weeks_per_year);
        // Staffing comes from the calculator rather than being typed in: 96
        // hours of coverage a week against its billable hours per person.
        $this->assertSame(4, $estimate->staff_required);
        $this->assertTrue($estimate->hasBudgetDashboard());
        $this->assertEquals(29.38, $estimate->budget_scenario['meta']['baselineWage']);
        $this->assertNotNull($estimate->budget_scenario_at);
    }

    public function test_the_vendor_edition_reports_every_figure_in_full(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor);
        $this->actingAs($vendor)->post(route('vendor-estimates.budget.attach', $estimate));

        $html = $this->render($estimate->fresh(), masked: false);

        // All three blocks GASQ asked for.
        $this->assertStringContainsString('Cost to Protect Summary', $html);
        $this->assertStringContainsString('Budget Summary', $html);
        $this->assertStringContainsString('Budget Line Items', $html);

        $this->assertStringContainsString('$538,769', $html, 'buyer internal annual cost');
        $this->assertStringContainsString('$377,138', $html, 'vendor annual cost');
        $this->assertStringContainsString('$161,630', $html, 'capital recovered');
        $this->assertStringContainsString('$107.93', $html, 'buyer internal hourly rate');
        $this->assertStringContainsString('$75.55', $html, 'vendor hourly rate');
        $this->assertStringContainsString('8.4 months', $html, 'payback period');
        $this->assertStringContainsString('4 FTE', $html, 'staff required');
        $this->assertStringNotContainsString('$•', $html, 'nothing is withheld from the vendor');
    }

    public function test_the_buyer_edition_withholds_the_vendor_figures_but_not_the_buyers_own(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor);
        $this->actingAs($vendor)->post(route('vendor-estimates.budget.attach', $estimate));
        // Price above the calculator's modelled vendor rate, so the quoted bill
        // rate and the withheld vendor cost are two distinct figures. Filling
        // from the calculator leaves them equal, and then the buyer can read the
        // withheld figure straight off the price they are being quoted.
        $this->patch(route('vendor-estimates.update', $estimate), [
            'title' => $estimate->title, 'scope' => $estimate->scope,
            'hourly_rate' => 82.00, 'weekly_hours' => 96, 'weeks_per_year' => 52, 'one_time_cost' => 0,
            'valid_until' => $estimate->valid_until->toDateString(), 'status' => 'draft',
        ])->assertRedirect();

        $html = $this->render($estimate->fresh(), masked: true);
        $this->assertStringContainsString('$82.00', $html, 'the quoted bill rate is the price, and is shown');

        // Withheld: everything that belongs to the vendor's side of the deal.
        $this->assertStringNotContainsString('$377,138', $html, 'vendor annual cost');
        $this->assertStringNotContainsString('$75.55', $html, 'vendor hourly rate');
        $this->assertStringNotContainsString('$113.32', $html, 'vendor overtime rate');
        $this->assertStringNotContainsString('$161,630', $html, 'capital recovered');
        $this->assertStringNotContainsString('8.4 months', $html, 'payback period');
        $this->assertStringContainsString('$•', $html, 'withheld figures are veiled, not removed');
        $this->assertStringContainsString('Vendor figures withheld', $html);

        // Reported in full: the buyer's own cost to protect and the coverage.
        $this->assertStringContainsString('$538,769', $html, 'buyer internal annual cost');
        $this->assertStringContainsString('$107.93', $html, 'buyer internal hourly rate');
        $this->assertStringContainsString('4 FTE', $html, 'staff required');
        $this->assertStringContainsString('4,992', $html, 'annual coverage hours');
    }

    public function test_the_emailed_copy_is_the_buyer_edition(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor, ['status' => 'ready']);
        $this->actingAs($vendor)->post(route('vendor-estimates.budget.attach', $estimate));

        $real = app(ReportService::class);
        $used = [];
        $spy = \Mockery::mock(ReportService::class);
        $spy->shouldReceive('calculatorPdf')->andReturnUsing(function (...$args) use ($real, &$used) {
            $used[] = $args[0];

            return $real->calculatorPdf(...$args);
        });
        $this->app->instance(ReportService::class, $spy);

        $this->post(route('vendor-estimates.send', $estimate), [
            'pdf_password' => 'BuyerEditionPassword',
            'request_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertSame(['vendor-lead-estimate-buyer'], $used, 'the buyer is emailed the masked edition');
    }

    public function test_the_attached_scenario_is_frozen_when_the_calculator_changes_later(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor);
        $this->actingAs($vendor)->post(route('vendor-estimates.budget.attach', $estimate));

        // The vendor reworks the calculator after the estimate went out.
        $this->saveCalculatorScenario($vendor, baselineWage: 45.00);

        $this->assertEquals(29.38, $estimate->fresh()->budget_scenario['meta']['baselineWage']);
        $this->assertStringContainsString('$538,769', $this->render($estimate->fresh(), masked: false));

        // Asking for the refresh explicitly is what moves it on.
        $this->post(route('vendor-estimates.budget.attach', $estimate))->assertRedirect();
        $this->assertEquals(45.00, $estimate->fresh()->budget_scenario['meta']['baselineWage']);
    }

    public function test_removing_the_dashboard_leaves_the_pricing_alone(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor);
        $this->actingAs($vendor)->post(route('vendor-estimates.budget.attach', $estimate));

        $this->delete(route('vendor-estimates.budget.detach', $estimate))->assertRedirect();
        $estimate->refresh();

        $this->assertFalse($estimate->hasBudgetDashboard());
        $this->assertNull($estimate->budget_scenario_at);
        $this->assertEquals(75.55, (float) $estimate->hourly_rate, 'the filled pricing stays');
        $this->assertStringNotContainsString('Cost to Protect Summary', $this->render($estimate, masked: false));
    }

    public function test_filling_without_a_saved_scenario_explains_what_to_do_first(): void
    {
        $vendor = $this->vendor();
        $estimate = $this->estimate($vendor);

        $this->actingAs($vendor)->get(route('vendor-estimates.edit', $estimate))
            ->assertOk()
            ->assertSee('No saved scenario yet')
            ->assertDontSee('Fill from my Know Before You Bid figures');

        $this->post(route('vendor-estimates.budget.attach', $estimate))
            ->assertRedirect()
            ->assertSessionHasErrors('budget');
        $this->assertFalse($estimate->fresh()->hasBudgetDashboard());
        $this->assertEquals(25.50, (float) $estimate->fresh()->hourly_rate, 'pricing is untouched');
    }

    public function test_a_new_estimate_can_be_prepared_straight_from_the_calculator(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $lead = $this->lead($vendor);

        // The create page offers the handoff, and fills from it when asked.
        $this->actingAs($vendor)->get(route('vendor-estimates.create', $lead))
            ->assertOk()->assertSee('Fill from my Know Before You Bid figures');
        $this->get(route('vendor-estimates.create', ['lead' => $lead, 'from_budget' => 1]))
            ->assertOk()
            ->assertSee('value="75.55"', false)
            ->assertSee('value="96.00"', false)
            ->assertSee('value="4"', false)
            ->assertSee('Pricing filled from your Know Before You Bid figures');

        $this->post(route('vendor-estimates.store', $lead), [
            'title' => 'Lobby service estimate', 'scope' => 'Two officers on weekdays.',
            'hourly_rate' => 75.55, 'weekly_hours' => 96, 'weeks_per_year' => 52, 'one_time_cost' => 0,
            'valid_until' => now()->addDays(30)->toDateString(), 'status' => 'draft',
            'attach_budget' => 1,
        ])->assertRedirect();

        $estimate = $lead->estimates()->firstOrFail();
        $this->assertTrue($estimate->hasBudgetDashboard());
        $this->assertEquals(29.38, $estimate->budget_scenario['meta']['baselineWage']);
    }

    public function test_the_covering_email_describes_the_edition_that_was_actually_attached(): void
    {
        $vendor = $this->vendor();
        $this->saveCalculatorScenario($vendor);
        $estimate = $this->estimate($vendor, ['status' => 'ready']);
        $this->actingAs($vendor)->post(route('vendor-estimates.budget.attach', $estimate));
        $this->post(route('vendor-estimates.send', $estimate), [
            'pdf_password' => 'BuyerEditionPassword', 'request_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $delivery = $estimate->deliveries()->firstOrFail();
        $this->assertTrue($delivery->snapshot['has_dashboard'], 'the snapshot records what the built PDF carried');

        // Detaching afterwards must not change what the already-built PDF says.
        $this->delete(route('vendor-estimates.budget.detach', $estimate));
        $html = view('emails.vendor-lead-estimate', ['delivery' => $delivery->fresh()])->render();
        $this->assertStringContainsString('Buyer Edition', $html);
        $this->assertStringContainsString('are withheld', $html);
    }

    public function test_another_vendor_cannot_attach_or_remove_this_estimates_dashboard(): void
    {
        $estimate = $this->estimate($this->vendor());
        $other = $this->vendor();
        $this->saveCalculatorScenario($other);

        $this->actingAs($other)->post(route('vendor-estimates.budget.attach', $estimate))->assertNotFound();
        $this->delete(route('vendor-estimates.budget.detach', $estimate))->assertNotFound();
        $this->assertFalse($estimate->fresh()->hasBudgetDashboard());
    }
}
