<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BuyerQuestionnairePostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_open_job_create_page(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone' => '4045557890',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($buyer)->get(route('jobs.create'));

        $response->assertOk();
        $response->assertSeeText('Post Your Security Service Request');
        $response->assertSeeText('Step 1: Service and Job Site');
    }

    public function test_buyer_quick_start_redirects_to_questionnaire_step_with_starter_data(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($buyer)->post(route('jobs.create.start'), [
            'starter_service_type' => 'Mobile Patrol',
            'location' => '200 Main St, Atlanta, GA',
            'zip_code' => '30303',
        ]);

        $response->assertRedirect(route('jobs.create', ['step' => 'details']));
        $response->assertSessionHas('job_posting_starter', function (array $starter): bool {
            return $starter['service_label'] === 'Mobile Patrol'
                && $starter['category'] === 'Mobile Patrol'
                && $starter['location'] === '200 Main St, Atlanta, GA'
                && $starter['zip_code'] === '30303';
        });
    }

    public function test_buyer_questionnaire_step_shows_quick_start_summary(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($buyer)
            ->withSession([
                'job_posting_starter' => [
                    'starter_service_type' => 'Unarmed Security Guard',
                    'service_label' => 'Unarmed Security Guard',
                    'service_types' => ['Unarmed Security Guard'],
                    'category' => 'Unarmed Security Guard',
                    'title' => 'Unarmed Security Guard request for 123 Peachtree St, Atlanta, GA',
                    'location' => '123 Peachtree St, Atlanta, GA',
                    'zip_code' => '30303',
                ],
            ])
            ->get(route('jobs.create', ['step' => 'details']));

        $response->assertOk();
        $response->assertSeeText('Step 2: Buyer Questionnaire');
        $response->assertSeeText('Requested Service');
        $response->assertSeeText('Unarmed Security Guard');
        $response->assertSeeText('123 Peachtree St, Atlanta, GA');
    }

    public function test_buyer_can_generate_announcement_preview_from_questionnaire(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($buyer)->post(route('jobs.preview'), $this->validPostingPayload());

        $response->assertRedirect(route('jobs.review'));
        $response->assertSessionHas('job_posting_preview', function (array $preview): bool {
            return ($preview['payload']['title'] ?? null) === 'Downtown Office Security'
                && ($preview['payload']['status'] ?? null) === 'open'
                && ($preview['payload']['questionnaire_data']['property_site_name'] ?? null) === 'Buckhead Tower';
        });
    }

    public function test_buyer_can_publish_generated_announcement_from_review(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $this->actingAs($buyer)->post(route('jobs.preview'), $this->validPostingPayload())
            ->assertRedirect(route('jobs.review'));

        $publishResponse = $this->actingAs($buyer)->post(route('jobs.publish'));

        $job = JobPosting::query()->latest('id')->first();

        $this->assertNotNull($job);
        $this->assertSame('open', $job?->status);
        $this->assertSame('Buckhead Tower', $job?->questionnaire('property_site_name'));

        $publishResponse->assertRedirect(route('jobs.show', $job));
        $publishResponse->assertSessionHas('success', 'Job announcement published successfully.');
        $this->assertNull(session('job_posting_preview'));
    }

    public function test_buyer_posting_request_persists_questionnaire_snapshot_on_job_posting(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $response = $this->actingAs($buyer)->post(route('jobs.store'), $this->validPostingPayload());

        $response
            ->assertRedirect(route('job-board'))
            ->assertSessionHas('success', 'Job posted successfully.');

        $job = JobPosting::query()->latest('id')->first();

        $this->assertNotNull($job);
        $this->assertSame('Buckhead Tower', $job?->questionnaire('property_site_name'));
        $this->assertSame('123 Peachtree St, Atlanta, GA 30303', $job?->questionnaire('business_address'));
        $this->assertSame(['new_requirement', 'budget_planning'], $job?->questionnaire('project_readiness_reasons'));
        $this->assertSame('flexible_budget', $job?->questionnaire('funds_approval_status'));
        $this->assertSame(24, $job?->questionnaire('hours_per_day'));
        $this->assertSame(52, $job?->questionnaire('weeks_per_year'));
        $this->assertTrue((bool) $job?->questionnaire('phone_verified'));
        $this->assertContains('Business address: 123 Peachtree St, Atlanta, GA 30303', $job?->special_requirements ?? []);
    }

    public function test_buyer_posting_request_persists_extended_form_fields_and_uploads(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $payload = array_merge($this->validPostingPayload(), [
            'property_type' => 'Other',
            'property_type_other' => 'Mixed-Use Campus',
            'service_types' => ['Unarmed Security Guard', 'Other'],
            'service_type_other' => 'Executive Protection',
            'assignment_type' => 'hybrid',
            'patrol_types' => ['Foot Patrol', 'Vehicle Patrol'],
            'duties_required' => ['Observe and Report', 'Other'],
            'duties_other' => 'Metal detector screening',
            'cost_comparison_requested' => 'yes',
            'officer_licensing_required' => 'depends_on_assignment',
            'background_checks_required' => 'yes',
            'drug_testing_required' => 'yes',
            'uniformed_officers_required' => 'yes',
            'insurance_minimums_required' => ['General Liability', 'Workers Compensation'],
            'compliance_terms' => 'Minimum $2M general liability and site-specific badge training.',
            'additional_notes_to_vendors' => 'Please note lobby staffing starts one week early.',
            'supporting_documents' => [
                UploadedFile::fake()->create('post-orders.pdf', 128, 'application/pdf'),
                UploadedFile::fake()->create('site-map.docx', 64, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ],
        ]);

        $response = $this->actingAs($buyer)->post(route('jobs.store'), $payload);

        $response
            ->assertRedirect(route('job-board'))
            ->assertSessionHas('success', 'Job posted successfully.');

        $job = JobPosting::query()->latest('id')->first();

        $this->assertNotNull($job);
        $this->assertSame('Mixed-Use Campus', $job?->questionnaire('property_type_other'));
        $this->assertSame('Executive Protection', $job?->questionnaire('service_type_other'));
        $this->assertSame(['Foot Patrol', 'Vehicle Patrol'], $job?->questionnaire('patrol_types'));
        $this->assertSame('Metal detector screening', $job?->questionnaire('duties_other'));
        $this->assertSame('yes', $job?->questionnaire('cost_comparison_requested'));
        $this->assertSame('depends_on_assignment', $job?->questionnaire('officer_licensing_required'));
        $this->assertSame(['General Liability', 'Workers Compensation'], $job?->questionnaire('insurance_minimums_required'));
        $this->assertSame('Please note lobby staffing starts one week early.', $job?->questionnaire('additional_notes_to_vendors'));
        $this->assertCount(2, $job?->questionnaire('supporting_documents') ?? []);

        foreach ($job?->questionnaire('supporting_documents', []) ?? [] as $document) {
            Storage::disk('public')->assertExists($document['path']);
        }
    }

    public function test_buyer_posting_request_enforces_phone_verification_and_conditional_fields(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '4045557890',
            'phone_verified' => false,
        ]);

        $payload = array_merge($this->validPostingPayload(), [
            'property_type' => 'Other',
            'property_type_other' => '',
            'service_types' => ['Unarmed Security Guard', 'Other'],
            'service_type_other' => '',
            'assignment_type' => 'patrol_route',
            'patrol_types' => [],
            'duties_required' => ['Observe and Report', 'Other'],
            'duties_other' => '',
        ]);

        $response = $this->actingAs($buyer)->from(route('jobs.create'))->post(route('jobs.store'), $payload);

        $response
            ->assertRedirect(route('jobs.create'))
            ->assertSessionHasErrors([
                'contact_phone',
                'property_type_other',
                'service_type_other',
                'patrol_types',
                'duties_other',
            ]);
    }

    public function test_unverified_buyer_can_open_details_step_and_is_prompted_to_verify_inline(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '+14045557890',
            'phone_verified' => false,
        ]);

        $starterPayload = [
            'starter_service_type' => 'Unarmed Security Guard',
            'location' => '123 Main Street, Atlanta, GA',
            'zip_code' => '30303',
            'latitude' => '33.7488',
            'longitude' => '-84.3877',
            'google_place_id' => 'place_123',
        ];

        $this->actingAs($buyer)
            ->post(route('jobs.create.start'), $starterPayload)
            ->assertRedirect(route('jobs.create', ['step' => 'details']))
            ->assertSessionHas('job_posting_starter');

        $response = $this->actingAs($buyer)
            ->withSession([
                'job_posting_starter' => [
                    'starter_service_type' => 'Unarmed Security Guard',
                    'starter_service_type_other' => null,
                    'service_types' => ['Unarmed Security Guard'],
                    'service_type_other' => null,
                    'service_label' => 'Unarmed Security Guard',
                    'category' => 'Unarmed Security Guard',
                    'title' => 'Unarmed Security Guard - 123 Main Street, Atlanta, GA',
                    'location' => '123 Main Street, Atlanta, GA',
                    'zip_code' => '30303',
                    'latitude' => '33.7488',
                    'longitude' => '-84.3877',
                    'google_place_id' => 'place_123',
                ],
            ])
            ->get(route('jobs.create', ['step' => 'details']));

        $response->assertOk();
        $response->assertSeeText('Verify Mobile Number by SMS');
        $response->assertSeeText('Send Verification Code');
        $response->assertSeeText('Verification Required');
    }

    public function test_verified_buyer_cannot_post_when_request_phone_does_not_match_verified_account_phone(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'company' => 'Acme Properties',
            'phone' => '+14045551234',
            'phone_verified' => true,
        ]);

        $payload = array_merge($this->validPostingPayload(), [
            'contact_phone' => '+14706332816',
        ]);

        $response = $this->actingAs($buyer)->from(route('jobs.create'))->post(route('jobs.store'), $payload);

        $response
            ->assertRedirect(route('jobs.create'))
            ->assertSessionHasErrors([
                'contact_phone',
            ]);
    }

    public function test_open_bid_offer_uses_questionnaire_snapshot_for_summary_fields(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'name' => 'Buyer User',
            'email' => 'buyer@example.com',
            'phone' => '4045557890',
            'phone_verified' => true,
            'state' => 'GA',
            'zip_code' => '30336',
        ]);

        $vendor = User::factory()->create([
            'user_type' => 'vendor',
        ]);
        $secondVendor = User::factory()->create([
            'user_type' => 'vendor',
        ]);
        $thirdVendor = User::factory()->create([
            'user_type' => 'vendor',
        ]);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Atlanta Security Coverage',
            'category' => 'Unarmed Security Officer',
            'location' => 'Atlanta, GA',
            'zip_code' => '30336',
            'service_start_date' => '2026-05-01',
            'guards_per_shift' => 6,
            'budget_min' => 393120,
            'budget_max' => 393120,
            'property_type' => 'Commercial Office',
            'questionnaire_data' => [
                'phone_verified' => true,
                'project_readiness_reasons' => ['new_requirement', 'budget_planning'],
                'final_decision_maker' => 'yes',
                'move_forward_if_accepted' => 'yes',
                'funds_approval_status' => 'flexible_budget',
                'multiple_locations' => 'yes',
                'locations_count' => 2,
                'service_types' => ['Unarmed Security Guard', 'Access Control'],
                'request_type' => 'new_service',
                'budget_format' => 'monthly_budget',
                'monthly_budget' => 14000,
                'service_start_timeline' => '30_60_days',
                'allow_scope_adjustment' => 'yes',
                'hours_per_day' => 24,
                'days_per_week' => 7,
                'weeks_per_year' => 52,
                'guards_per_shift' => 6,
            ],
        ]);

        $bid = Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $vendor->id,
            'amount' => 393120,
            'status' => 'accepted',
            'message' => 'We can take this assignment.',
        ]);

        Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $secondVendor->id,
            'amount' => 393120,
            'status' => 'accepted',
        ]);

        Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $thirdVendor->id,
            'amount' => 393120,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($vendor)->get(route('open-bid-offer.index', ['bid' => $bid->id]));

        $response->assertOk();
        $response->assertSeeText('ALERT! GASQNOW New Security Project in Atlanta, GA');
        $response->assertSeeText('Unarmed Security Officer');
        $response->assertSeeText('b****@e*****e.com');
        $response->assertSeeText('(404) ***-****');
        $response->assertSeeText('What is this service for?');
        $response->assertSeeText('New Purchase of Services');
        $response->assertSeeText('Are you the person authorized to make a final buying commitment with the vendor or approve payment for the proposed services?');
        $response->assertSeeText('Are you price shopping?');
        $response->assertSeeText("No, I'm ready to purchase at a fair & reasonable price");
        $response->assertSeeText('How likely are you to make a hiring decision?');
        $response->assertSeeText("I'm ready to hire right now");
        $response->assertSeeText('What is your sense of urgency for hiring a security vendor to start this security project?');
        $response->assertSeeText('Within 30-45 days');
        $response->assertSeeInOrder([
            'Total Credits to Respond:',
            '3,931',
        ]);
        $response->assertSeeInOrder([
            'Responses:',
            '3/5 Professionals have accepted bid offer',
        ]);
        $response->assertSeeInOrder([
            'Total Monthly Hours Hired to Work:',
            '728',
        ]);
        $response->assertDontSeeText('Appraisal Fee:');
    }

    public function test_buyer_can_view_their_own_bid_summary(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
            'email' => 'buyer@example.com',
            'phone' => '4045557890',
            'phone_verified' => true,
        ]);

        $vendor = User::factory()->create([
            'user_type' => 'vendor',
        ]);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Security Coverage',
            'category' => 'Unarmed Security Officer',
            'location' => 'Atlanta, GA',
            'zip_code' => '30336',
            'service_start_date' => '2026-05-01',
            'guards_per_shift' => 2,
            'property_type' => 'Commercial Office',
            'questionnaire_data' => [
                'phone_verified' => true,
                'final_decision_maker' => 'authorized_representative',
                'approval_authority' => '25000_49999',
                'funds_approval_status' => 'restrictive_budget',
                'move_forward_if_accepted' => 'yes',
                'multiple_locations' => 'no',
                'service_types' => ['Access Control'],
                'request_type' => 'expand_existing_coverage',
                'hours_per_day' => 8,
                'days_per_week' => 5,
                'weeks_per_year' => 26,
                'guards_per_shift' => 2,
            ],
        ]);

        $bid = Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $vendor->id,
            'amount' => 100000,
            'status' => 'accepted',
        ]);

        $this->actingAs($buyer)
            ->get(route('open-bid-offer.index', ['bid' => $bid->id]))
            ->assertOk()
            ->assertSeeText('Access Control')
            ->assertSeeText('b****@e*****e.com');
    }

    public function test_open_bid_offer_blocks_unrelated_vendor_access(): void
    {
        $buyer = User::factory()->create([
            'user_type' => 'buyer',
        ]);

        $ownerVendor = User::factory()->create([
            'user_type' => 'vendor',
        ]);

        $otherVendor = User::factory()->create([
            'user_type' => 'vendor',
        ]);

        $job = JobPosting::query()->create([
            'user_id' => $buyer->id,
            'title' => 'Security Coverage',
            'category' => 'Unarmed Security Officer',
            'location' => 'Atlanta, GA',
            'service_start_date' => '2026-05-01',
            'guards_per_shift' => 2,
            'property_type' => 'Commercial Office',
        ]);

        $bid = Bid::query()->create([
            'job_posting_id' => $job->id,
            'user_id' => $ownerVendor->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);

        $this->actingAs($otherVendor)
            ->get(route('open-bid-offer.index', ['bid' => $bid->id]))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPostingPayload(): array
    {
        return [
            'title' => 'Downtown Office Security',
            'category' => 'Unarmed Security Officer',
            'location' => '123 Peachtree St, Atlanta, GA',
            'zip_code' => '30303',
            'service_start_date' => '2026-05-15',
            'guards_per_shift' => 3,
            'property_type' => 'Commercial Office',
            'contact_name' => 'Jordan Buyer',
            'contact_job_title' => 'Operations Director',
            'organization_name' => 'Acme Properties',
            'property_site_name' => 'Buckhead Tower',
            'contact_email' => 'jordan@example.com',
            'contact_phone' => '+1 (404) 555-1234',
            'business_address' => '123 Peachtree St, Atlanta, GA 30303',
            'preferred_contact_method' => 'email',
            'best_time_to_contact' => 'morning',
            'final_decision_maker' => 'yes',
            'approval_authority' => '50000_plus',
            'knows_true_inhouse_cost' => 'yes',
            'project_readiness_reasons' => ['new_requirement', 'budget_planning'],
            'service_start_timeline' => '30_60_days',
            'funds_approval_status' => 'flexible_budget',
            'budget_type' => 'monthly',
            'budget_amount_range' => '$12,000-$16,000 monthly',
            'true_internal_cost_calculated' => 'yes',
            'if_pricing_exceeds' => ['adjust_scope', 'change_service_level'],
            'current_security_setup' => 'none',
            'is_replacing_provider' => 'no',
            'multiple_bids_required' => 'yes',
            'willing_adjust_scope_to_budget' => 'yes',
            'move_forward_if_accepted' => 'yes',
            'risk_assessment_last_12_months' => 'no_want_one',
            'multiple_locations' => 'no',
            'service_types' => ['Unarmed Security Guard', 'Access Control'],
            'request_type' => 'new_service',
            'desired_contract_term' => '12 months',
            'primary_reason' => 'We are staffing a new lobby security function.',
            'hours_per_day' => 24,
            'days_per_week' => 7,
            'weeks_per_year' => 52,
            'shifts_needed' => ['Day Shift', 'Overnight Shift'],
            'assignment_type' => 'dedicated_post',
            'duties_required' => ['Observe and Report', 'Access Control'],
            'service_package_expectation' => 'observe_and_report_only',
            'hands_off_expected' => 'yes',
            'has_written_post_orders' => 'in_progress',
            'known_site_risks' => 'Occasional trespassing after hours.',
            'budget_format' => 'monthly_budget',
            'monthly_budget' => 14000,
            'willing_post_offer' => 'yes',
            'allow_scope_adjustment' => 'yes',
            'cost_comparison_requested' => 'yes',
            'officer_licensing_required' => 'yes',
            'background_checks_required' => 'yes',
            'drug_testing_required' => 'no',
            'uniformed_officers_required' => 'yes',
            'insurance_minimums_required' => ['General Liability', 'Auto Liability'],
            'compliance_terms' => 'Standard site badge and insurance certificate requirements.',
            'vendor_response_deadline' => '2026-05-01',
            'additional_notes_to_vendors' => 'Please review lobby visitor traffic before bidding.',
            'buyer_certification' => '1',
            'consent_to_contact' => '1',
        ];
    }
}
