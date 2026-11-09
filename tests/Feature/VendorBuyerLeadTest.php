<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorLeadLink;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorBuyerLeadTest extends TestCase
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
        return User::factory()->create(['user_type' => 'vendor', 'company' => 'North Security', 'nda_accepted_at' => now()]);
    }

    private function link(User $vendor): VendorLeadLink
    {
        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk();

        return VendorLeadLink::where('vendor_id', $vendor->id)->firstOrFail();
    }

    private function details(array $overrides = []): array
    {
        return array_merge(['contact_name' => 'Avery Buyer', 'email' => 'avery@example.test', 'phone' => '+14045550123', 'company' => 'Buyer Company', 'site_address' => '100 Main Street', 'service_type' => 'Unarmed guarding', 'scope' => 'Two officers covering the lobby on weekdays.', 'weekly_hours' => 80, 'start_date' => now()->addWeek()->toDateString()], $overrides);
    }

    public function test_each_vendor_gets_a_stable_unique_link_and_account_free_form(): void
    {
        $vendor = $this->vendor();
        $first = $this->link($vendor);
        $this->assertSame($first->token, $this->link($vendor)->token);
        $other = $this->link($this->vendor());
        $this->assertNotSame($first->token, $other->token);
        $this->assertSame(64, strlen($first->token));
        auth()->forgetGuards();
        $this->get($first->invitationUrl())->assertOk()->assertSee('North Security')->assertSee('Scope of services')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('vendor_lead_links', 2);
    }

    public function test_submission_is_attributed_by_link_and_cannot_inject_vendor_status_or_notes(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        $other = $this->vendor();
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['vendor_id' => $other->id, 'status' => 'won', 'notes' => 'injected']))->assertRedirect($link->invitationUrl())->assertSessionHas('enquiry_received');
        $this->assertDatabaseHas('vendor_buyer_leads', ['vendor_id' => $vendor->id, 'email' => 'avery@example.test', 'status' => 'new', 'notes' => null]);
        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk()->assertSee('Avery Buyer')->assertSee('Two officers covering the lobby');
        $this->actingAs($other)->get(route('vendor-buyer-leads.index'))->assertOk()->assertDontSee('Avery Buyer');
    }

    public function test_vendor_can_manage_own_leads_and_filter_but_cannot_access_another_vendors_leads(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        $this->post(route('buyer-enquiries.store', $link->token), $this->details());
        $lead = VendorBuyerLead::firstOrFail();
        $this->patch(route('vendor-buyer-leads.update', $lead), ['status' => 'qualified', 'notes' => 'Arrange a scope review.'])->assertRedirect();
        $this->assertDatabaseHas('vendor_buyer_leads', ['id' => $lead->id, 'status' => 'qualified', 'notes' => 'Arrange a scope review.']);
        $this->get(route('vendor-buyer-leads.index', ['status' => 'new']))->assertDontSee('Avery Buyer');
        $this->get(route('vendor-buyer-leads.index', ['status' => 'qualified', 'q' => 'Avery']))->assertSee('Avery Buyer');
        $this->patch(route('vendor-buyer-leads.update', $lead), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->actingAs($this->vendor())->patch(route('vendor-buyer-leads.update', $lead), ['status' => 'lost'])->assertNotFound();
        $this->assertSame('qualified', $lead->fresh()->status);
    }

    public function test_replacing_link_invalidates_old_link_and_preserves_existing_leads(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        $oldToken = $link->token;
        $this->post(route('buyer-enquiries.store', $oldToken), $this->details());
        $this->post(route('vendor-buyer-leads.rotate'))->assertRedirect();
        $newToken = $link->fresh()->token;
        $this->assertNotSame($oldToken, $newToken);
        $this->get(route('buyer-enquiries.show', $oldToken))->assertNotFound();
        $this->post(route('buyer-enquiries.store', $oldToken), $this->details())->assertNotFound();
        $this->get(route('buyer-enquiries.show', $newToken))->assertOk();
        $this->assertDatabaseCount('vendor_buyer_leads', 1);
    }

    public function test_invalid_links_and_invalid_contact_scope_are_rejected(): void
    {
        $link = $this->link($this->vendor());
        $this->get(route('buyer-enquiries.show', 'invalid'))->assertNotFound();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['email' => 'bad', 'scope' => '', 'weekly_hours' => -1, 'start_date' => 'yesterday']))->assertSessionHasErrors(['email', 'scope', 'weekly_hours', 'start_date']);
        $this->assertDatabaseCount('vendor_buyer_leads', 0);
        $link->vendor->update(['user_type' => 'buyer']);
        $this->get($link->invitationUrl())->assertNotFound();
    }

    public function test_guests_and_buyers_cannot_manage_leads_or_invitation_links(): void
    {
        $this->get(route('vendor-buyer-leads.index'))->assertRedirect(route('login'));
        $buyer = User::factory()->create(['user_type' => 'buyer', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->get(route('vendor-buyer-leads.index'))->assertForbidden();
        $this->post(route('vendor-buyer-leads.rotate'))->assertForbidden();
        $this->assertDatabaseCount('vendor_lead_links', 0);
    }
}
