<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorLeadEstimate;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorLeadEstimateTest extends TestCase
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

    private function inputs(array $changes = []): array
    {
        return array_merge(['title' => 'Lobby service estimate', 'scope' => 'Two officers on weekdays.', 'hourly_rate' => 25.50, 'weekly_hours' => 80, 'weeks_per_year' => 52, 'one_time_cost' => 500, 'valid_until' => now()->addDays(30)->toDateString(), 'terms' => 'Subject to site review.', 'status' => 'draft'], $changes);
    }

    private function estimate(User $vendor, array $changes = []): VendorLeadEstimate
    {
        $lead = $this->lead($vendor);
        $this->actingAs($vendor)->post(route('vendor-estimates.store', $lead), $this->inputs($changes))->assertRedirect();

        return $lead->estimates()->firstOrFail();
    }

    public function test_vendor_can_review_submission_prepare_edit_and_filter_estimates_with_server_totals(): void
    {
        $vendor = $this->vendor();
        $lead = $this->lead($vendor);
        $this->actingAs($vendor)->get(route('vendor-estimates.create', $lead))->assertOk()->assertSee('Lobby security on weekdays.')->assertSee('avery@example.test');
        $this->post(route('vendor-estimates.store', $lead), $this->inputs(['vendor_id' => 999, 'number' => 'FORGED', 'currency' => 'EUR', 'buyer' => ['email' => 'forged@example.test'], 'annual_cost' => 1]))->assertRedirect();
        $estimate = $lead->estimates()->firstOrFail();
        $this->assertSame($vendor->id, $estimate->vendor_id);
        $this->assertSame('USD', $estimate->currency);
        $this->assertSame('avery@example.test', $estimate->buyer['email']);
        $this->assertStringStartsWith('GASQ-EST-', $estimate->number);
        $this->assertEquals(['weekly' => 2040, 'monthly' => 8840, 'annual' => 106080, 'first_year' => 106580, 'annual_hours' => 4160], $estimate->totals());
        $this->get(route('vendor-estimates.edit', $estimate))->assertOk()->assertSee('106,580.00');
        $this->patch(route('vendor-estimates.update', $estimate), $this->inputs(['hourly_rate' => 30, 'status' => 'ready']))->assertRedirect();
        $this->assertEquals(124800, $estimate->fresh()->totals()['annual']);
        $this->get(route('vendor-estimates.index', ['status' => 'ready', 'lead' => $lead->id]))->assertOk()->assertSee($estimate->number);
        $this->get(route('vendor-estimates.index', ['status' => 'draft']))->assertDontSee($estimate->number);
        $this->get(route('vendor-buyer-leads.index'))->assertOk()->assertSee('View estimates (1)');
    }

    public function test_other_vendors_cannot_create_edit_update_download_or_list_another_vendors_estimate(): void
    {
        $estimate = $this->estimate($this->vendor(), ['status' => 'ready']);
        $this->actingAs($this->vendor());
        $this->get(route('vendor-estimates.create', $estimate->lead))->assertNotFound();
        $this->post(route('vendor-estimates.store', $estimate->lead), $this->inputs())->assertNotFound();
        $this->get(route('vendor-estimates.edit', $estimate))->assertNotFound();
        $this->patch(route('vendor-estimates.update', $estimate), $this->inputs())->assertNotFound();
        $this->post(route('vendor-estimates.download', $estimate), ['pdf_password' => 'TestEstimatePassword'])->assertNotFound();
        $this->get(route('vendor-estimates.index'))->assertOk()->assertDontSee($estimate->number)->assertDontSee('Avery Buyer');
    }

    public function test_protected_pdf_requires_ready_status_current_validity_and_strong_password_without_storing_it(): void
    {
        $estimate = $this->estimate($this->vendor());
        $url = route('vendor-estimates.download', $estimate);
        $this->post($url, ['pdf_password' => 'TestEstimatePassword'])->assertStatus(422);
        $estimate->update(['status' => 'ready']);
        $this->post($url, [])->assertSessionHasErrors('pdf_password');
        $this->post($url, ['pdf_password' => 'short'])->assertSessionHasErrors('pdf_password');
        $response = $this->post($url, ['pdf_password' => 'TestEstimatePassword'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Encrypt', $response->getContent());
        $this->assertStringNotContainsString('TestEstimatePassword', $response->getContent());
        $this->assertStringNotContainsString('TestEstimatePassword', $estimate->fresh()->toJson());
        $estimate->update(['valid_until' => now()->subDay()]);
        $this->post($url, ['pdf_password' => 'TestEstimatePassword'])->assertStatus(422);
        $estimate->update(['status' => 'archived']);
        $this->post($url, ['pdf_password' => 'TestEstimatePassword'])->assertStatus(422);
    }

    public function test_invalid_prices_and_scope_are_rejected_and_archiving_retains_estimate(): void
    {
        $estimate = $this->estimate($this->vendor());
        $this->patch(route('vendor-estimates.update', $estimate), $this->inputs(['hourly_rate' => -1, 'weekly_hours' => 0, 'weeks_per_year' => 53, 'scope' => '', 'one_time_cost' => -1]))->assertSessionHasErrors(['hourly_rate', 'weekly_hours', 'weeks_per_year', 'scope', 'one_time_cost']);
        $this->patch(route('vendor-estimates.update', $estimate), $this->inputs(['status' => 'archived', 'valid_until' => now()->subDay()->toDateString()]))->assertRedirect();
        $this->assertSame('archived', $estimate->fresh()->status);
        $this->assertDatabaseCount('vendor_lead_estimates', 1);
    }

    public function test_guests_and_buyers_cannot_access_dashboard(): void
    {
        $this->get(route('vendor-estimates.index'))->assertRedirect(route('login'));
        $buyer = User::factory()->create(['user_type' => 'buyer', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->get(route('vendor-estimates.index'))->assertForbidden();
    }
}
