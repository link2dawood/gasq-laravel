<?php

namespace App\Http\Controllers;

use App\Models\AppraisalReview;
use App\Models\CalculatorState;
use App\Services\AppraisalReviewService;
use App\Services\CalendlyAppraisalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAppraisalReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.appraisal-reviews', [
            'reviews' => AppraisalReview::with('appointments')->latest()->paginate(20),
            'states' => CalculatorState::with('user')->where('calculator_type', 'budget-calculator')->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'calculator_state_id' => 'required|integer|exists:calculator_states,id',
            'buyer_name' => 'required|string|max:255', 'buyer_email' => 'required|email|max:255',
        ]);
        $state = CalculatorState::findOrFail($data['calculator_state_id']);
        abort_unless($state->calculator_type === 'budget-calculator', 422);
        AppraisalReview::create($data + [
            'created_by' => $request->user()->id, 'report_number' => 'GASQ-APPRAISAL-'.Str::upper((string) Str::uuid()),
        ]);

        return back()->with('success', 'Review created. Use its buyer-specific booking link, then finalize the approved report.');
    }

    public function finalize(Request $request, AppraisalReview $review, AppraisalReviewService $service): RedirectResponse
    {
        abort_if($request->query->has('pdf_password'), 422, 'PDF passwords must be supplied in the form body.');
        $data = $request->validate(['pdf_password' => 'required|string|min:8|max:32', 'approved_final' => 'accepted']);
        $service->finalize($review, $data['pdf_password']);

        return back()->with('success', 'Protected final report saved. Retain the password separately and provide it personally during the appointment.');
    }

    public function linkAppointment(Request $request, AppraisalReview $review, CalendlyAppraisalService $calendly): RedirectResponse
    {
        $data = $request->validate(['invitee_uri' => 'required|string|max:255']);
        abort_unless($calendly->ingest($data['invitee_uri'], $review->id), 422, 'The booking must match this buyer and the configured appraisal event type.');

        return back()->with('success', 'Existing Calendly appointment linked and verified.');
    }
}
