<?php

namespace App\Http\Controllers;

use App\Models\ContentSection;
use App\Models\Faq;
use App\Models\PricingPlan;
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
        $faqs = Faq::where('is_active', true)->orderBy('order')->get();
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
