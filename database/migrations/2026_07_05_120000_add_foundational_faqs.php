<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the foundational buyer/vendor FAQs (the "biggest questions" people ask
     * when first using GASQ) and reorder the existing operational FAQs to sit
     * beneath them, so the value-proposition content leads on each page.
     */
    public function up(): void
    {
        $now = now();

        // Foundational FAQs first (orders 2-6 per audience); the shared intro
        // "What is GASQ?" stays at order 1 above them.
        foreach ($this->foundationalFaqs() as $faq) {
            DB::table('faqs')->updateOrInsert(
                ['question' => $faq['question']],
                array_merge($faq, ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]),
            );
        }

        // Push the existing operational FAQs into a higher order band so they
        // follow the foundational content. Missing rows (e.g. renamed by an
        // admin) are simply skipped.
        foreach ($this->operationalOrder() as $question => $order) {
            DB::table('faqs')->where('question', $question)->update(['order' => $order]);
        }
    }

    public function down(): void
    {
        foreach ($this->foundationalFaqs() as $faq) {
            DB::table('faqs')->where('question', $faq['question'])->delete();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function foundationalFaqs(): array
    {
        return [
            // ---- Buyer ----
            [
                'question' => 'What makes GASQ different from a traditional bidding platform?',
                'answer' => 'GASQ is a procurement technology platform, not a bid marketplace. Instead of encouraging vendors to undercut one another, GASQ helps buyers make informed purchasing decisions through standardized pricing analysis, procurement documentation, and a structured vendor selection process. Vendors compete on qualifications, experience, responsiveness, and scope—not by racing to the lowest price.',
                'audience' => 'buyer',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 2,
            ],
            [
                'question' => 'How do I know the pricing is fair?',
                'answer' => 'GASQ uses its Cost to Protect™ methodology to evaluate the total cost of delivering security services. Our appraisal process helps buyers understand whether pricing is financially realistic, operationally sustainable, and procurement-ready before a contract is awarded.',
                'audience' => 'buyer',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 3,
            ],
            [
                'question' => 'Can vendors negotiate their price?',
                'answer' => "No. Vendors may accept or decline the buyer's offer. They may negotiate the scope of work if adjustments are needed, but they cannot negotiate the agreed price. This creates a transparent procurement process and eliminates vendor-to-vendor price shopping.",
                'audience' => 'buyer',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 4,
            ],
            [
                'question' => 'Why am I required to interview vendors before awarding a contract?',
                'answer' => 'Pricing is only one part of a successful security program. GASQ requires buyers to interview qualified vendors so they can evaluate experience, staffing capabilities, communication, technology, and cultural fit before making a final award.',
                'audience' => 'buyer',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 5,
            ],
            [
                'question' => 'What documentation do I receive?',
                'answer' => "Depending on your package, buyers may receive:\n• A GASQ Cost to Protect™ Appraisal\n• Procurement documentation\n• Vendor qualification reports\n• Interview scorecards\n• Award recommendations\n• Procurement audit trail for internal approval",
                'audience' => 'buyer',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 6,
            ],

            // ---- Vendor ----
            [
                'question' => 'Will my price be shared with competing vendors?',
                'answer' => 'No. GASQ does not permit vendor-to-vendor price shopping. Your pricing remains confidential throughout the procurement process. Buyers evaluate vendors based on qualifications and interviews before any final pricing decisions.',
                'audience' => 'vendor',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 2,
            ],
            [
                'question' => 'How do I win work on GASQ?',
                'answer' => "Success is based on more than price. Buyers evaluate:\n• Experience\n• Licensing\n• Insurance\n• Staffing capability\n• Operational readiness\n• Interview performance\n• Ability to meet the requested scope\nThe goal is to award the best-qualified vendor—not simply the cheapest.",
                'audience' => 'vendor',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 3,
            ],
            [
                'question' => 'Can I negotiate after receiving an offer?',
                'answer' => "You may:\n• Accept the offer\n• Decline the offer\n• Request changes to the scope of work\nHowever, you may not negotiate the agreed price. This protects pricing integrity for all parties.",
                'audience' => 'vendor',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 4,
            ],
            [
                'question' => 'Why does GASQ verify pricing?',
                'answer' => 'GASQ is designed to help ensure security pricing is financially realistic and sustainable. This reduces the likelihood of underpriced contracts, service failures, excessive turnover, and hidden costs that often occur when contracts are awarded solely on the lowest bid.',
                'audience' => 'vendor',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 5,
            ],
            [
                'question' => 'Why should I use GASQ instead of responding to traditional RFPs?',
                'answer' => 'Traditional procurement often rewards the lowest bidder, leading to price compression and unsustainable contracts. GASQ allows vendors to compete on value, operational capability, and service quality while protecting them from unnecessary vendor-to-vendor price shopping.',
                'audience' => 'vendor',
                'cta_label' => null,
                'cta_url' => null,
                'order' => 6,
            ],
        ];
    }

    /**
     * Existing operational FAQs, reordered to follow the foundational block.
     *
     * @return array<string, int>
     */
    private function operationalOrder(): array
    {
        return [
            'What is GASQ?' => 1,
            'How do credits work?' => 20,
            'Can I get a refund?' => 21,
            'What calculators are available?' => 22,
            'Is my payment information secure?' => 23,
            'How do I post a job?' => 24,
            'How do I become a vendor?' => 25,
            'How do I accept a bid?' => 26,
        ];
    }
};
