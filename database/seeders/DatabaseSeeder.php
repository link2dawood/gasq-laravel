<?php

namespace Database\Seeders;

/**
 * Seeded: User (3 buyers, 3 vendors, 1 admin), Wallet, VendorProfile,
 *        Transaction, JobPosting (6), Bid (9), DiscoveryCall (6), FeatureUsageRule,
 *        PricingPlan, Setting, AnalyticsEvent (16), Faq (8).
 * Not seeded: cache, sessions, jobs (queue), password_reset_tokens.
 *
 * Batches: All required app tables are seeded. No further batches required.
 * Optional: more users/jobs/bids/events/FAQs can be added for load or UI testing.
 */

use App\Models\AnalyticsEvent;
use App\Models\Bid;
use App\Models\DiscoveryCall;
use App\Models\Faq;
use App\Models\FeatureUsageRule;
use App\Models\JobPosting;
use App\Models\PricingPlan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // Buyer
        $buyer = User::factory()->create([
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'password' => $password,
            'user_type' => 'buyer',
            'company' => null,
            'phone' => null,
        ]);
        $buyerWallet = Wallet::create(['user_id' => $buyer->id, 'balance' => 20]);

        // Vendor (with profile)
        $vendor = User::factory()->create([
            'name' => 'Test Vendor',
            'email' => 'vendor@example.com',
            'password' => $password,
            'user_type' => 'vendor',
            'company' => 'SecureGuard Solutions',
            'phone' => '+1 555-0100',
        ]);
        $vendorWallet = Wallet::create(['user_id' => $vendor->id, 'balance' => 10]);
        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'SecureGuard Solutions',
            'description' => 'Full-service security provider. Guard services, mobile patrol, and consulting.',
            'phone' => '+1 555-0100',
            'address' => '123 Security Ave, Your City',
            'capabilities' => ['guards', 'mobile_patrol', 'consulting'],
            'is_verified' => true,
        ]);

        // Admin
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => $password,
            'user_type' => 'admin',
            'company' => null,
            'phone' => null,
        ]);
        $adminWallet = Wallet::create(['user_id' => $admin->id, 'balance' => 0]);

        // Extra buyer
        $buyer2 = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => $password,
            'user_type' => 'buyer',
            'company' => null,
            'phone' => null,
        ]);
        Wallet::create(['user_id' => $buyer2->id, 'balance' => 15]);

        // Extra vendor (with profile)
        $vendor2 = User::factory()->create([
            'name' => 'Mike Wilson',
            'email' => 'mike@example.com',
            'password' => $password,
            'user_type' => 'vendor',
            'company' => 'NightWatch Patrol',
            'phone' => '+1 555-0200',
        ]);
        Wallet::create(['user_id' => $vendor2->id, 'balance' => 8]);
        VendorProfile::create([
            'user_id' => $vendor2->id,
            'company_name' => 'NightWatch Patrol',
            'description' => 'Mobile patrol and event security. 24/7 coverage available.',
            'phone' => '+1 555-0200',
            'address' => '456 Patrol Way, City',
            'capabilities' => ['mobile_patrol', 'event_security'],
            'is_verified' => false,
        ]);

        // Third buyer
        $buyer3 = User::factory()->create([
            'name' => 'Alex Rivera',
            'email' => 'alex@example.com',
            'password' => $password,
            'user_type' => 'buyer',
            'company' => null,
            'phone' => null,
        ]);
        Wallet::create(['user_id' => $buyer3->id, 'balance' => 25]);

        // Third vendor (with profile)
        $vendor3 = User::factory()->create([
            'name' => 'Sarah Chen',
            'email' => 'sarah@example.com',
            'password' => $password,
            'user_type' => 'vendor',
            'company' => 'First Response Security',
            'phone' => '+1 555-0300',
        ]);
        Wallet::create(['user_id' => $vendor3->id, 'balance' => 12]);
        VendorProfile::create([
            'user_id' => $vendor3->id,
            'company_name' => 'First Response Security',
            'description' => 'Armed and unarmed guards, access control, and emergency response.',
            'phone' => '+1 555-0300',
            'address' => '789 Response Lane, City',
            'capabilities' => ['guards', 'armed', 'access_control'],
            'is_verified' => true,
        ]);

        // Wallet transactions (tokens history)
        // Buyer: purchase credits then use calculators
        Transaction::create([
            'user_id' => $buyer->id,
            'tokens_change' => 30,
            'type' => 'purchase',
            'reference_type' => 'checkout',
            'reference_id' => 'ORDER-1001',
            'description' => 'Purchased 30 credits starter pack',
            'balance_after' => 30,
        ]);
        Transaction::create([
            'user_id' => $buyer->id,
            'tokens_change' => -5,
            'type' => 'spend',
            'reference_type' => 'instant_estimator',
            'reference_id' => 'IE-1',
            'description' => 'Instant estimator run from dashboard',
            'balance_after' => 25,
        ]);
        Transaction::create([
            'user_id' => $buyer->id,
            'tokens_change' => -5,
            'type' => 'spend',
            'reference_type' => 'main_menu_calculator',
            'reference_id' => 'MMC-1',
            'description' => 'Main menu calculator scenario',
            'balance_after' => $buyerWallet->balance,
        ]);

        // Vendor: small purchase and one calculator use
        Transaction::create([
            'user_id' => $vendor->id,
            'tokens_change' => 15,
            'type' => 'purchase',
            'reference_type' => 'checkout',
            'reference_id' => 'ORDER-2001',
            'description' => 'Purchased 15 credits',
            'balance_after' => 15,
        ]);
        Transaction::create([
            'user_id' => $vendor->id,
            'tokens_change' => -5,
            'type' => 'spend',
            'reference_type' => 'contract_analysis',
            'reference_id' => 'CA-1',
            'description' => 'Ran contract analysis on sample contract',
            'balance_after' => $vendorWallet->balance,
        ]);

        // Admin: grant and use internal credits (net 0)
        Transaction::create([
            'user_id' => $admin->id,
            'tokens_change' => 10,
            'type' => 'bonus',
            'reference_type' => 'system',
            'reference_id' => 'ADMIN-INIT',
            'description' => 'Initial admin credits for testing',
            'balance_after' => 10,
        ]);
        Transaction::create([
            'user_id' => $admin->id,
            'tokens_change' => -10,
            'type' => 'spend',
            'reference_type' => 'analytics',
            'reference_id' => 'REPORT-1',
            'description' => 'Used credits while viewing analytics flows',
            'balance_after' => $adminWallet->balance,
        ]);

        // Job postings (buyer)
        $job1 = JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Night shift security guards - warehouse',
            'category' => 'guards',
            'location' => 'Industrial Park, City',
            'service_start_date' => now()->addWeeks(2),
            'service_end_date' => now()->addMonths(3),
            'guards_per_shift' => 2,
            'budget_min' => 4500,
            'budget_max' => 6000,
            'description' => 'Need two unarmed guards for night shift at our warehouse. 12-hour shifts, 6 nights per week.',
            'property_type' => 'warehouse',
            'special_requirements' => ['cctv_monitoring', 'patrol'],
            'expires_at' => now()->addWeek(),
        ]);
        $job2 = JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Mobile patrol - retail strip',
            'category' => 'mobile_patrol',
            'location' => 'Downtown',
            'service_start_date' => now()->addMonth(),
            'service_end_date' => null,
            'guards_per_shift' => 1,
            'budget_min' => 1200,
            'budget_max' => 2000,
            'description' => 'Weekly mobile patrol for a small retail strip. 3–4 visits per week.',
            'property_type' => 'retail',
            'special_requirements' => null,
            'expires_at' => now()->addWeeks(2),
        ]);
        $job3 = JobPosting::create([
            'user_id' => $buyer2->id,
            'title' => 'Event security - corporate conference',
            'category' => 'guards',
            'location' => 'Convention Center',
            'service_start_date' => now()->addWeeks(3),
            'service_end_date' => now()->addWeeks(3)->addDays(2),
            'guards_per_shift' => 4,
            'budget_min' => 8000,
            'budget_max' => 12000,
            'description' => 'Three-day conference. Need 4 guards for access control and floor monitoring.',
            'property_type' => 'conference',
            'special_requirements' => ['badge_check', 'crowd_management'],
            'expires_at' => now()->addWeeks(1),
        ]);
        $job4 = JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Armed guard - high-value site',
            'category' => 'guards',
            'location' => 'Financial District',
            'service_start_date' => now()->addMonth(),
            'service_end_date' => null,
            'guards_per_shift' => 1,
            'budget_min' => 6500,
            'budget_max' => 9000,
            'description' => 'Single armed guard, 12h day shift, 5 days/week. Licensed only.',
            'property_type' => 'office',
            'special_requirements' => ['armed', 'licensed'],
            'expires_at' => now()->addWeeks(3),
        ]);
        $job5 = JobPosting::create([
            'user_id' => $buyer3->id,
            'title' => 'Weekend event security - outdoor festival',
            'category' => 'guards',
            'location' => 'Riverside Park',
            'service_start_date' => now()->addWeeks(4),
            'service_end_date' => now()->addWeeks(4)->addDays(2),
            'guards_per_shift' => 6,
            'budget_min' => 15000,
            'budget_max' => 22000,
            'description' => 'Three-day outdoor festival. Need 6 guards for gates, perimeter, and crowd control.',
            'property_type' => 'outdoor_event',
            'special_requirements' => ['crowd_management', 'first_aid_trained'],
            'expires_at' => now()->addWeeks(2),
        ]);
        $job6 = JobPosting::create([
            'user_id' => $buyer->id,
            'title' => 'Access control - office building',
            'category' => 'guards',
            'location' => 'Business Park',
            'service_start_date' => now()->addWeeks(1),
            'service_end_date' => null,
            'guards_per_shift' => 2,
            'budget_min' => 7000,
            'budget_max' => 9500,
            'description' => 'Two guards for lobby and access control. Business hours, 5 days/week.',
            'property_type' => 'office',
            'special_requirements' => ['access_control', 'reception'],
            'expires_at' => now()->addWeeks(2),
        ]);

        // Bids (vendor on jobs)
        Bid::create([
            'job_posting_id' => $job1->id,
            'user_id' => $vendor->id,
            'amount' => 5200,
            'status' => 'accepted',
            'message' => 'We can start within 10 days. Rate includes two guards, uniforms, and reporting.',
            'proposal' => 'Standard 12-hour night shift coverage, incident reporting, and access to our portal.',
            'responded_at' => now(),
        ]);
        Bid::create([
            'job_posting_id' => $job2->id,
            'user_id' => $vendor->id,
            'amount' => 1600,
            'status' => 'pending',
            'message' => 'We cover this area. Happy to do a walk-through first.',
            'proposal' => null,
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job2->id,
            'user_id' => $vendor2->id,
            'amount' => 1450,
            'status' => 'pending',
            'message' => 'NightWatch can start next week. Competitive rate.',
            'proposal' => null,
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job3->id,
            'user_id' => $vendor2->id,
            'amount' => 9500,
            'status' => 'pending',
            'message' => 'We specialize in event security. Can provide 4 licensed guards.',
            'proposal' => 'Full coverage for 3 days, incident reports, and liaison on site.',
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job3->id,
            'user_id' => $vendor->id,
            'amount' => 10500,
            'status' => 'pending',
            'message' => 'SecureGuard can cover. We have armed and unarmed options.',
            'proposal' => null,
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job4->id,
            'user_id' => $vendor->id,
            'amount' => 7200,
            'status' => 'pending',
            'message' => 'We have licensed armed guards. Available from your start date.',
            'proposal' => null,
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job5->id,
            'user_id' => $vendor2->id,
            'amount' => 18500,
            'status' => 'pending',
            'message' => 'NightWatch does festivals regularly. We can cover all three days.',
            'proposal' => '6 guards, first-aid certified, radio comms included.',
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job5->id,
            'user_id' => $vendor3->id,
            'amount' => 19800,
            'status' => 'pending',
            'message' => 'First Response can staff 6. We have crowd-control and first-aid certs.',
            'proposal' => null,
            'responded_at' => null,
        ]);
        Bid::create([
            'job_posting_id' => $job6->id,
            'user_id' => $vendor3->id,
            'amount' => 8200,
            'status' => 'accepted',
            'message' => 'We specialize in lobby and access control. Can start next week.',
            'proposal' => 'Two guards, visitor sign-in, badge support.',
            'responded_at' => now(),
        ]);

        // Discovery calls
        DiscoveryCall::create([
            'user_id' => $buyer->id,
            'requested_at' => now()->addDays(3),
            'status' => 'requested',
            'notes' => 'Discuss enterprise contract options',
        ]);
        DiscoveryCall::create([
            'user_id' => $vendor->id,
            'requested_at' => now()->addDays(1),
            'status' => 'confirmed',
            'notes' => 'Onboarding call',
        ]);
        DiscoveryCall::create([
            'user_id' => $buyer2->id,
            'requested_at' => now()->addDays(5),
            'status' => 'requested',
            'notes' => 'Discuss multi-site pricing',
        ]);
        DiscoveryCall::create([
            'user_id' => $vendor2->id,
            'requested_at' => now()->addDays(2),
            'status' => 'cancelled',
            'notes' => 'Reschedule later',
        ]);
        DiscoveryCall::create([
            'user_id' => $buyer3->id,
            'requested_at' => now()->addDays(4),
            'status' => 'requested',
            'notes' => 'Large event package quote',
        ]);
        DiscoveryCall::create([
            'user_id' => $vendor3->id,
            'requested_at' => now()->addDays(1),
            'status' => 'completed',
            'notes' => 'Initial onboarding completed',
        ]);

        FeatureUsageRule::insert([
            ['feature_key' => 'instant_estimator', 'feature_name' => 'Instant Estimator', 'tokens_required' => 2, 'description' => 'Run GASQ instant estimator', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['feature_key' => 'contract_analysis', 'feature_name' => 'Contract Analysis', 'tokens_required' => 3, 'description' => 'Contract analysis report', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['feature_key' => 'main_menu_calculator', 'feature_name' => 'Main Menu Calculator', 'tokens_required' => 5, 'description' => 'Full calculator suite', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Pricing plans (credit packages) — use create() so features array is cast to JSON
        PricingPlan::create(['name' => 'Starter', 'price' => 29.00, 'tokens_included' => 30, 'features' => ['Instant estimator', 'Email support'], 'is_active' => true, 'sort_order' => 1]);
        PricingPlan::create(['name' => 'Professional', 'price' => 79.00, 'tokens_included' => 100, 'features' => ['All calculators', 'Contract analysis', 'Priority support'], 'is_active' => true, 'sort_order' => 2]);
        PricingPlan::create(['name' => 'Enterprise', 'price' => 199.00, 'tokens_included' => 300, 'features' => ['Everything in Pro', 'Discovery call', 'Dedicated support'], 'is_active' => true, 'sort_order' => 3]);

        // App settings
        Setting::insert([
            ['key' => 'site_name', 'value' => 'GASQ', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_email', 'value' => 'support@example.com', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'credits_currency', 'value' => 'USD', 'group' => 'billing', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Example analytics events for dashboard — use create() so event_data array is cast to JSON
        $analyticsEvents = [
            ['event_type' => 'page_view', 'user_id' => $buyer->id, 'event_data' => ['path' => '/', 'route' => 'landing'], 'session_id' => 'session-buyer-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subDays(1)],
            ['event_type' => 'calculator_run', 'user_id' => $buyer->id, 'event_data' => ['calculator' => 'instant_estimator', 'tokens_spent' => 2], 'session_id' => 'session-buyer-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(20)],
            ['event_type' => 'calculator_run', 'user_id' => $buyer->id, 'event_data' => ['calculator' => 'main_menu_calculator', 'tokens_spent' => 5], 'session_id' => 'session-buyer-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(18)],
            ['event_type' => 'job_posted', 'user_id' => $buyer->id, 'event_data' => ['job_id' => $job1->id, 'title' => $job1->title], 'session_id' => 'session-buyer-2', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(12)],
            ['event_type' => 'bid_submitted', 'user_id' => $vendor->id, 'event_data' => ['job_id' => $job1->id, 'bid_amount' => 5200], 'session_id' => 'session-vendor-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(10)],
            ['event_type' => 'discovery_call_requested', 'user_id' => $buyer->id, 'event_data' => ['channel' => 'web', 'source' => 'dashboard'], 'session_id' => 'session-buyer-3', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(6)],
            ['event_type' => 'admin_analytics_viewed', 'user_id' => $admin->id, 'event_data' => ['path' => '/admin/analytics'], 'session_id' => 'session-admin-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(2)],
            ['event_type' => 'page_view', 'user_id' => $buyer2->id, 'event_data' => ['path' => '/pricing', 'route' => 'pricing'], 'session_id' => 'session-buyer2-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subDays(2)],
            ['event_type' => 'page_view', 'user_id' => null, 'event_data' => ['path' => '/job-board', 'route' => 'job-board'], 'session_id' => 'session-guest-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(15)],
            ['event_type' => 'page_view', 'user_id' => $buyer2->id, 'event_data' => ['path' => '/faq', 'route' => 'faq'], 'session_id' => 'session-buyer2-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(14)],
            ['event_type' => 'job_viewed', 'user_id' => $buyer2->id, 'event_data' => ['job_id' => $job1->id], 'session_id' => 'session-buyer2-2', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(8)],
            ['event_type' => 'vendor_profile_viewed', 'user_id' => $buyer->id, 'event_data' => ['vendor_id' => $vendor->id], 'session_id' => 'session-buyer-4', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(5)],
            ['event_type' => 'calculator_run', 'user_id' => $buyer2->id, 'event_data' => ['calculator' => 'mobile_patrol_calculator', 'tokens_spent' => 2], 'session_id' => 'session-buyer2-2', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(4)],
            ['event_type' => 'page_view', 'user_id' => $buyer3->id, 'event_data' => ['path' => '/marketplace-landing', 'route' => 'marketplace-landing'], 'session_id' => 'session-buyer3-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subDays(3)],
            ['event_type' => 'job_posted', 'user_id' => $buyer3->id, 'event_data' => ['job_id' => $job5->id, 'title' => $job5->title], 'session_id' => 'session-buyer3-2', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(7)],
            ['event_type' => 'bid_submitted', 'user_id' => $vendor3->id, 'event_data' => ['job_id' => $job6->id, 'bid_amount' => 8200], 'session_id' => 'session-vendor3-1', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHours(3)],
            ['event_type' => 'page_view', 'user_id' => $vendor3->id, 'event_data' => ['path' => '/credits', 'route' => 'credits'], 'session_id' => 'session-vendor3-2', 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subHour()],
        ];
        foreach ($analyticsEvents as $payload) {
            $at = $payload['created_at'];
            unset($payload['created_at']);
            AnalyticsEvent::create(array_merge($payload, ['created_at' => $at, 'updated_at' => $at]));
        }

        Faq::insert([
            ['question' => 'What is GASQ?', 'answer' => 'GASQ connects buyers and vendors of security services with transparent pricing and tools.', 'order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'How do credits work?', 'answer' => 'Credits are used to run calculators and premium features. You can purchase credit packages from the Credits page.', 'order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'Can I get a refund?', 'answer' => 'Unused credits may be refundable within 30 days. Contact support for details.', 'order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'How do I post a job?', 'answer' => 'Register as a buyer, then go to the Job Board and create a new posting with your requirements and budget.', 'order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'How do I become a vendor?', 'answer' => 'Sign up and choose Vendor. Complete your company profile to appear on the marketplace and bid on jobs.', 'order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'What calculators are available?', 'answer' => 'GASQ offers instant estimator, main menu calculator, contract analysis, security billing, and mobile patrol tools. Each uses credits per run.', 'order' => 6, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'How do I accept a bid?', 'answer' => 'From your job posting, view bids and use the Accept button. You can message the vendor before accepting.', 'order' => 7, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'Is my payment information secure?', 'answer' => 'Yes. We use industry-standard encryption and do not store full card numbers. Billing is handled by our secure payment provider.', 'order' => 8, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
