<?php

namespace App\Http\Controllers;

use App\Models\ContentSection;
use App\Models\Faq;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PageController extends Controller
{
    public function landing(): View
    {
        return view('landing');
    }

    public function marketplaceLanding(): View
    {
        return view('pages.marketplace-landing');
    }

    public function pricing(): View
    {
        return $this->renderPricing(null, 'Pricing');
    }

    public function buyerPricing(): View
    {
        return $this->renderPricing('buyer', 'Pricing — For Buyers');
    }

    public function vendorPricing(): View
    {
        return $this->renderPricing('vendor', 'Pricing — For Vendors');
    }

    private function renderPricing(?string $audience, string $pricingTitle): View
    {
        $query = PricingPlan::where('is_active', true);

        if ($audience !== null && Schema::hasColumn('pricing_plans', 'audience')) {
            $query->whereIn('audience', ['all', $audience]);
        }

        $plans = $query->orderBy('sort_order')->get();

        return view('pages.pricing', compact('plans', 'audience', 'pricingTitle'));
    }

    public function faq(): View
    {
        // The generic /faq adapts to the logged-in role; guests/admins see all.
        $user = auth()->user();
        $audience = $user?->isBuyer() ? 'buyer' : ($user?->isVendor() ? 'vendor' : null);
        return $this->renderFaq($audience, 'Frequently Asked Questions');
    }

    public function buyerFaq(): View
    {
        return $this->renderFaq('buyer', 'Buyer FAQ');
    }

    public function vendorFaq(): View
    {
        return $this->renderFaq('vendor', 'Vendor FAQ');
    }

    private function renderFaq(?string $audience, string $faqTitle): View
    {
        $query = Faq::where('is_active', true);

        if ($audience !== null && Schema::hasColumn('faqs', 'audience')) {
            $query->whereIn('audience', ['all', $audience]);
        }

        $faqs = $query->orderBy('order')->get();

        return view('pages.faq', compact('faqs', 'faqTitle'));
    }

    public function payScale(): View
    {
        $sections = ContentSection::forPage(ContentSection::PAGE_PAYSCALE)->activeOrdered()->get();

        return view('pages.payscale', compact('sections'));
    }

    public function paymentPolicy(): View
    {
        $sections = ContentSection::forPage(ContentSection::PAGE_PAYMENT_POLICY)->activeOrdered()->get();

        return view('pages.payment-policy', compact('sections'));
    }

    public function terms(): View
    {
        return view('pages.terms-conditions');
    }

    public function privacy(): View
    {
        return view('pages.privacy-policy');
    }

    public function postCoverageSchedule(): View
    {
        $sections = ContentSection::forPage(ContentSection::PAGE_POST_COVERAGE_SCHEDULE)->activeOrdered()->get();

        return view('pages.post-coverage-schedule', compact('sections'));
    }
}
