<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            if (! Schema::hasColumn('faqs', 'audience')) {
                // 'all' (everyone), 'buyer' (buyer dashboard only), 'vendor' (vendor dashboard only)
                $table->string('audience')->default('all')->after('answer');
            }
            if (! Schema::hasColumn('faqs', 'cta_label')) {
                $table->string('cta_label')->nullable()->after('audience');
            }
            if (! Schema::hasColumn('faqs', 'cta_url')) {
                $table->string('cta_url')->nullable()->after('cta_label');
            }
        });

        // Normalise the existing seeded FAQ rows: clearer copy, correct audience,
        // and a "go to this page" link where the answer describes one.
        foreach ($this->faqUpdates() as $question => $attrs) {
            DB::table('faqs')->where('question', $question)->update($attrs);
        }
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            foreach (['audience', 'cta_label', 'cta_url'] as $col) {
                if (Schema::hasColumn('faqs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function faqUpdates(): array
    {
        return [
            'What is GASQ?' => [
                'answer' => 'Get A Security Quote (GASQ) connects buyers and vendors of security services with transparent, independent pricing and tools so you know your true Cost to Protect before you buy.',
                'audience' => 'all',
            ],
            'How do credits work?' => [
                'answer' => 'Credits are used to run calculators and generate reports. You can buy credit packages any time from the Credits page, and your balance is shown in the top navigation.',
                'audience' => 'all',
                'cta_label' => 'View Credits',
                'cta_url' => '/credits',
            ],
            'Can I get a refund?' => [
                'answer' => 'No. Credit purchases are final and non-refundable. Your credits never expire, so any you do not use stay in your account for future calculators and reports. If you believe you were charged in error, contact support and we will make it right.',
                'audience' => 'all',
            ],
            'How do I post a job?' => [
                'answer' => 'From your dashboard choose Post a Job, tell us the security service and job site, then complete the buyer questionnaire. GASQ qualifies the request and releases it to matched vendors.',
                'audience' => 'buyer',
                'cta_label' => 'Post a Job',
                'cta_url' => '/jobs/create',
            ],
            'How do I become a vendor?' => [
                'answer' => 'Create an account and choose Vendor, then complete your company profile to appear on the marketplace and respond to job offers.',
                'audience' => 'vendor',
                'cta_label' => 'Create a Vendor Account',
                'cta_url' => '/register',
            ],
            'What calculators are available?' => [
                'answer' => 'As a buyer you have the Instant Estimator and the Know Before You Buy Calculator. Vendors get the full calculator suite (Workforce Calculator, Mobile Patrol, Mobile Patrol Hit, Main Menu Calculator and more). Each run uses credits.',
                'audience' => 'all',
                'cta_label' => 'Open the Instant Estimator',
                'cta_url' => '/instant-estimator',
            ],
            'How do I accept a bid?' => [
                'answer' => 'When a vendor responds to your job offer you can review their bid and message them before making a decision from your job details page.',
                'audience' => 'vendor',
            ],
            'Is my payment information secure?' => [
                'answer' => 'Yes. We use industry-standard encryption and do not store full card numbers. Billing is handled by our secure payment provider.',
                'audience' => 'all',
            ],
        ];
    }
};
