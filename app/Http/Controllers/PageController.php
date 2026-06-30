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
        $plans = PricingPlan::where('is_active', true)->orderBy('sort_order')->get();
        return view('pages.pricing', compact('plans'));
    }

    public function faq(): View
    {
        $query = Faq::where('is_active', true);

        // Inside a user dashboard, only surface FAQs relevant to that role.
        // Guests and admins see the full list.
        if (Schema::hasColumn('faqs', 'audience')) {
            $user = auth()->user();
            if ($user && $user->isBuyer()) {
                $query->whereIn('audience', ['all', 'buyer']);
            } elseif ($user && $user->isVendor()) {
                $query->whereIn('audience', ['all', 'vendor']);
            }
        }

        $faqs = $query->orderBy('order')->get();
        return view('pages.faq', compact('faqs'));
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
