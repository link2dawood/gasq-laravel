<?php

namespace Tests\Feature;

use App\Mail\BuyerPrivateInvitationMail;
use App\Mail\BuyerQualificationSubmittedMail;
use App\Models\User;
use App\Models\VendorBuyerLead;
use App\Models\VendorLeadLink;
use App\Support\BuyerQualificationQuestionnaire;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VendorBuyerLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
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

    /** Every question answered "Yes" unless a test overrides one. */
    private function answers(array $overrides = []): array
    {
        $answers = array_fill_keys(array_keys(BuyerQualificationQuestionnaire::questions()), 'yes');

        return array_merge($answers, $overrides);
    }

    /**
     * A complete Opportunity Qualification Form. The default start date sits beyond the
     * mobilization window and inside the 45-day horizon, so the derived review outcome
     * is "Ready for qualification meeting" unless a test changes an answer or the date.
     */
    private function details(array $overrides = []): array
    {
        return array_merge([
            'company' => 'Buyer Company',
            'contact_name' => 'Avery Buyer',
            'contact_title' => 'Facilities Director',
            'email' => 'avery@example.test',
            'phone' => '+14045550123',
            'site_address' => '100 Main Street',
            'decision_maker_name' => 'Jordan Chief',
            'decision_maker_phone' => '+14045550124',
            'decision_maker_email' => 'jordan@example.test',
            'approved_budget' => 12000,
            'approved_budget_basis' => 'monthly',
            'service_types' => ['unarmed'],
            'service_type_other' => null,
            'industry' => 'Commercial property',
            'preferred_contact_method' => 'email',
            'scope' => 'Two officers covering the lobby on weekdays.',
            'start_date' => now()->addDays(30)->toDateString(),
            'service_end_date' => now()->addDays(395)->toDateString(),
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'officers_per_shift' => 2,
            'number_of_weeks' => 52,
            'answers' => $this->answers(),
        ], $overrides);
    }

    public function test_each_vendor_gets_a_stable_unique_link_and_account_free_form(): void
    {
        $vendor = $this->vendor();
        $first = $this->link($vendor);
        $this->assertSame($first->token, $this->link($vendor)->token);
        $other = $this->link($this->vendor());
        $this->assertNotSame($first->token, $other->token);
        $this->assertSame(64, strlen($first->token));
        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk()->assertSee('Draft invitation email')->assertSee(rawurlencode($first->invitationUrl()), false);
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
        $this->assertDatabaseHas('vendor_buyer_leads', ['vendor_id' => $vendor->id, 'email' => 'avery@example.test', 'status' => 'new', 'notes' => null, 'qualification_status' => 'ready_for_qualification_meeting']);
        $lead = VendorBuyerLead::firstOrFail();
        $this->assertSame('Unarmed', $lead->service_type);
        $this->assertSame('80.00', (string) $lead->weekly_hours);
        $this->assertSame('yes', $lead->qualification_answers['q23']);
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
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['email' => 'bad', 'scope' => '', 'officers_per_shift' => -1, 'start_date' => 'yesterday']))->assertSessionHasErrors(['email', 'scope', 'officers_per_shift', 'start_date']);
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['answers' => $this->answers(['q7' => '', 'q19' => 'maybe'])]))->assertSessionHasErrors(['answers.q7', 'answers.q19']);
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['service_types' => ['other'], 'service_type_other' => '']))->assertSessionHasErrors('service_type_other');
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['service_end_date' => now()->addDay()->toDateString()]))->assertSessionHasErrors('service_end_date');
        $this->assertDatabaseCount('vendor_buyer_leads', 0);
        $link->vendor->update(['user_type' => 'buyer']);
        $this->get($link->invitationUrl())->assertNotFound();
    }

    public function test_completed_form_is_emailed_to_the_vendor_and_copied_to_gasq(): void
    {
        config(['services.gasq.qualification_form_cc' => 'info@getasecurityquote.com']);
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details());

        Mail::assertSent(BuyerQualificationSubmittedMail::class, function (BuyerQualificationSubmittedMail $mail) use ($vendor) {
            return $mail->hasTo($vendor->email)
                && $mail->hasCc('info@getasecurityquote.com')
                && $mail->hasReplyTo('avery@example.test')
                && str_contains($mail->envelope()->subject, 'Ready for qualification meeting');
        });
    }

    public function test_the_submission_email_prints_the_whole_form(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details([
            'service_types' => ['unarmed', 'other'],
            'service_type_other' => 'Fire watch',
            'answers' => $this->answers(['q7' => 'no', 'q19' => 'na']),
        ]));

        $html = (new BuyerQualificationSubmittedMail(VendorBuyerLead::firstOrFail()))->render();

        $this->assertStringContainsString('Scope development needed', $html);
        $this->assertStringContainsString('Unarmed, Other (Fire watch)', $html);
        $this->assertStringContainsString('$12,000.00 monthly', $html);
        $this->assertStringContainsString('8 hours/day', $html);
        $this->assertStringContainsString('Clarify before the qualification meeting', $html);
        $this->assertStringContainsString('Jordan Chief', $html);
        $this->assertStringContainsString('N/A', $html);
    }

    public function test_the_copy_address_is_dropped_when_it_duplicates_the_vendor(): void
    {
        $vendor = $this->vendor();
        config(['services.gasq.qualification_form_cc' => $vendor->email]);
        $link = $this->link($vendor);
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details());

        Mail::assertSent(BuyerQualificationSubmittedMail::class, fn (BuyerQualificationSubmittedMail $mail) => $mail->hasTo($vendor->email) && $mail->cc === []);
    }

    public function test_internal_review_status_follows_the_forms_routing_rules(): void
    {
        $soon = now()->addDays(5);
        $far = now()->addDays(120);
        $inWindow = now()->addDays(30);

        $this->assertSame('urgent_feasibility_review', BuyerQualificationQuestionnaire::deriveStatus($this->answers(), $soon));
        $this->assertSame('future_opportunity', BuyerQualificationQuestionnaire::deriveStatus($this->answers(), $far));
        $this->assertSame('future_opportunity', BuyerQualificationQuestionnaire::deriveStatus($this->answers(['q11' => 'no']), $inWindow));
        $this->assertSame('hold_funding_or_approval_pending', BuyerQualificationQuestionnaire::deriveStatus($this->answers(['q3' => 'no']), $inWindow));
        $this->assertSame('hold_funding_or_approval_pending', BuyerQualificationQuestionnaire::deriveStatus($this->answers(['q18' => 'yes', 'q19' => 'no']), $inWindow));
        $this->assertSame('scope_development_needed', BuyerQualificationQuestionnaire::deriveStatus($this->answers(['q7' => 'no']), $inWindow));
        $this->assertSame('ready_for_qualification_meeting', BuyerQualificationQuestionnaire::deriveStatus($this->answers(['q19' => 'na', 'q22' => 'na']), $inWindow));
    }

    public function test_vendor_sees_the_answers_the_no_list_and_can_override_the_review_outcome(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details([
            'answers' => $this->answers(['q7' => 'no']),
        ]));
        $lead = VendorBuyerLead::firstOrFail();
        $this->assertSame('scope_development_needed', $lead->qualification_status);

        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk()
            ->assertSee('Scope development needed')
            ->assertSee('Clarify before the qualification meeting')
            ->assertSee('Have you identified the duties the security personnel will perform?', false)
            ->assertSee('Jordan Chief')
            ->assertSee('$12,000.00 monthly');

        $this->patch(route('vendor-buyer-leads.update', $lead), ['status' => 'contacted', 'qualification_status' => 'ready_for_qualification_meeting'])->assertRedirect();
        $this->assertSame('ready_for_qualification_meeting', $lead->fresh()->qualification_status);
        $this->patch(route('vendor-buyer-leads.update', $lead), ['status' => 'contacted', 'qualification_status' => 'invented'])->assertSessionHasErrors('qualification_status');
        $this->get(route('vendor-buyer-leads.index', ['qualification_status' => 'scope_development_needed']))->assertDontSee('Avery Buyer');
        $this->get(route('vendor-buyer-leads.index', ['qualification_status' => 'ready_for_qualification_meeting']))->assertSee('Avery Buyer');
    }

    public function test_vendor_sends_the_approved_private_invitation_with_every_placeholder_filled(): void
    {
        $vendor = $this->vendor();
        $vendor->update(['name' => 'Morgan Vendor', 'phone' => '+14045559000']);
        $link = $this->link($vendor);

        $this->actingAs($vendor)
            ->post(route('vendor-buyer-leads.send-invitation'), ['buyer_name' => 'Avery Buyer', 'buyer_email' => 'avery@example.test'])
            ->assertRedirect()->assertSessionHas('status');

        Mail::assertSent(BuyerPrivateInvitationMail::class, function (BuyerPrivateInvitationMail $mail) use ($vendor, $link) {
            $html = $mail->render();

            return $mail->hasTo('avery@example.test')
                && $mail->hasCc($vendor->email)
                && $mail->hasReplyTo($vendor->email)
                && $mail->envelope()->subject === 'Your Private Invitation to Begin Your Security Services Estimate'
                && str_contains($html, 'Hi Avery,')
                && ! str_contains($html, 'Avery Buyer,')
                && str_contains($html, 'Thank you for considering North Security for your security needs.')
                && str_contains($html, $link->invitationUrl())
                && str_contains($html, 'please return it directly to Morgan Vendor at')
                && str_contains($html, '+14045559000')
                && str_contains($html, 'GASQ Network Vendor')
                && str_contains($html, 'Know Before You Buy.')
                && ! str_contains($html, '[');
        });
    }

    public function test_invitation_requires_a_buyer_name_and_valid_email_and_is_vendor_only(): void
    {
        $vendor = $this->vendor();
        $this->actingAs($vendor)->post(route('vendor-buyer-leads.send-invitation'), ['buyer_name' => '', 'buyer_email' => 'nope'])
            ->assertSessionHasErrors(['buyer_name', 'buyer_email']);
        Mail::assertNothingSent();

        $buyer = User::factory()->create(['user_type' => 'buyer', 'nda_accepted_at' => now()]);
        $this->actingAs($buyer)->post(route('vendor-buyer-leads.send-invitation'), ['buyer_name' => 'Avery', 'buyer_email' => 'avery@example.test'])->assertForbidden();
        auth()->forgetGuards();
        $this->post(route('vendor-buyer-leads.send-invitation'), ['buyer_name' => 'Avery', 'buyer_email' => 'avery@example.test'])->assertRedirect(route('login'));
    }

    public function test_the_form_states_where_it_returns_to_and_carries_both_email_fields(): void
    {
        $vendor = $this->vendor();
        $vendor->update(['name' => 'Morgan Vendor']);
        $link = $this->link($vendor);
        auth()->forgetGuards();

        $this->get($link->invitationUrl())->assertOk()
            ->assertSee('Where this form is returned')
            ->assertSee('Your email address *', false)
            ->assertSee('Return to (vendor) *', false)
            ->assertSee('name="return_to_email"', false)
            ->assertSee($vendor->email)
            ->assertSee('Morgan Vendor');
    }

    public function test_completed_form_returns_to_the_vendor_and_a_buyer_copy_omits_the_internal_review(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['answers' => $this->answers(['q3' => 'no'])]));

        Mail::assertSent(BuyerQualificationSubmittedMail::class, 2);

        Mail::assertSent(BuyerQualificationSubmittedMail::class, function (BuyerQualificationSubmittedMail $mail) use ($vendor) {
            if ($mail->forBuyer) {
                return false;
            }

            return $mail->hasTo($vendor->email) && $mail->hasCc('info@getasecurityquote.com')
                && str_contains($mail->render(), 'Hold: funding or approval pending');
        });

        Mail::assertSent(BuyerQualificationSubmittedMail::class, function (BuyerQualificationSubmittedMail $mail) use ($vendor) {
            if (! $mail->forBuyer) {
                return false;
            }
            $html = $mail->render();

            return $mail->hasTo('avery@example.test')
                && $mail->hasReplyTo($vendor->email)
                && ! $mail->hasCc('info@getasecurityquote.com')
                && $mail->envelope()->subject === 'Your completed GASQ Buyer Intake Form'
                && str_contains($html, 'Your completed form has been returned to')
                && str_contains($html, 'Has funding for the security services been approved?')
                && ! str_contains($html, 'Internal review status')
                && ! str_contains($html, 'Hold: funding or approval pending')
                && ! str_contains($html, 'Clarify before the qualification meeting');
        });
    }

    public function test_a_tampered_return_address_cannot_redirect_the_submission(): void
    {
        $vendor = $this->vendor();
        $link = $this->link($vendor);
        auth()->forgetGuards();
        $this->post(route('buyer-enquiries.store', $link->token), $this->details(['return_to_email' => 'attacker@example.test']))
            ->assertSessionHasNoErrors();

        Mail::assertNotSent(BuyerQualificationSubmittedMail::class, fn (BuyerQualificationSubmittedMail $mail) => $mail->hasTo('attacker@example.test') || $mail->hasCc('attacker@example.test'));
        Mail::assertSent(BuyerQualificationSubmittedMail::class, fn (BuyerQualificationSubmittedMail $mail) => ! $mail->forBuyer && $mail->hasTo($vendor->email));
    }

    /**
     * The whole private invitation workflow, from the vendor's send action to the
     * completed form landing back with the right vendor: invitation out, buyer opens
     * the link taken from that email, buyer submits, both parties get the form, and
     * the lead is attributed to the sending vendor and nobody else.
     */
    public function test_full_private_invitation_workflow_returns_the_form_to_the_sending_vendor(): void
    {
        $vendor = $this->vendor();
        $vendor->update(['name' => 'Morgan Vendor', 'phone' => '+14045559000']);
        $otherVendor = $this->vendor();
        $this->link($vendor);

        $this->actingAs($vendor)
            ->post(route('vendor-buyer-leads.send-invitation'), ['buyer_name' => 'Avery Buyer', 'buyer_email' => 'avery@example.test'])
            ->assertRedirect();
        auth()->forgetGuards();

        // Take the link out of the sent invitation rather than trusting our own copy.
        $invitation = null;
        Mail::assertSent(BuyerPrivateInvitationMail::class, function (BuyerPrivateInvitationMail $mail) use (&$invitation) {
            $invitation = $mail;

            return true;
        });
        $invitationUrl = $invitation->link->invitationUrl();

        $this->get($invitationUrl)->assertOk()->assertSee('Morgan Vendor');
        $this->post(route('buyer-enquiries.store', $invitation->link->token), $this->details())
            ->assertRedirect($invitationUrl)->assertSessionHas('enquiry_received');

        $lead = VendorBuyerLead::firstOrFail();
        $this->assertSame($vendor->id, $lead->vendor_id);
        $this->assertSame('ready_for_qualification_meeting', $lead->qualification_status);
        $this->assertNotNull($lead->qualification_submitted_at);

        Mail::assertSent(BuyerQualificationSubmittedMail::class, fn ($mail) => ! $mail->forBuyer && $mail->hasTo($vendor->email));
        Mail::assertSent(BuyerQualificationSubmittedMail::class, fn ($mail) => $mail->forBuyer && $mail->hasTo('avery@example.test'));
        Mail::assertNotSent(BuyerQualificationSubmittedMail::class, fn ($mail) => $mail->hasTo($otherVendor->email) || $mail->hasCc($otherVendor->email));

        $this->actingAs($vendor)->get(route('vendor-buyer-leads.index'))->assertOk()->assertSee('Avery Buyer');
        $this->actingAs($otherVendor)->get(route('vendor-buyer-leads.index'))->assertOk()->assertDontSee('Avery Buyer');
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
