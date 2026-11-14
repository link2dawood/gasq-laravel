<?php

namespace App\Http\Controllers;

use App\Mail\BuyerPrivateInvitationMail;
use App\Mail\BuyerQualificationSubmittedMail;
use App\Models\VendorBuyerLead;
use App\Models\VendorLeadLink;
use App\Support\BuyerQualificationQuestionnaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VendorBuyerLeadController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isVendor(), 403);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(VendorBuyerLead::STATUSES))],
            'qualification_status' => ['nullable', Rule::in(array_keys(BuyerQualificationQuestionnaire::STATUSES))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $link = VendorLeadLink::firstOrCreate(['vendor_id' => $request->user()->id], ['token' => Str::random(64)]);
        $leads = VendorBuyerLead::where('vendor_id', $request->user()->id)->withCount('estimates')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['qualification_status'] ?? null, fn ($query, $status) => $query->where('qualification_status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('contact_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('company', 'like', '%'.$search.'%');
            }))
            ->latest()->latest('id')->paginate(15)->withQueryString();

        return view('vendor-buyer-leads.index', compact('link', 'leads'));
    }

    public function rotate(Request $request)
    {
        abort_unless($request->user()->isVendor(), 403);
        VendorLeadLink::updateOrCreate(['vendor_id' => $request->user()->id], ['token' => Str::random(64)]);

        return back()->with('status', 'New invitation link created. The previous link no longer accepts submissions.');
    }

    /**
     * Send a buyer the approved GASQ private invitation.
     *
     * The vendor supplies only the buyer's name and email. Everything else in the
     * message comes from their own account, so the wording stays the approved copy and
     * the buyer always gets the vendor's real contact details. The vendor is copied so
     * they hold the invitation they sent, and Reply-To points at them, not at GASQ.
     */
    public function sendInvitation(Request $request)
    {
        $vendor = $request->user();
        abort_unless($vendor->isVendor(), 403);

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['required', 'email', 'max:255'],
        ]);

        $link = VendorLeadLink::firstOrCreate(['vendor_id' => $vendor->id], ['token' => Str::random(64)]);

        try {
            $vendorCopy = strcasecmp($vendor->email, $data['buyer_email']) === 0 ? [] : [$vendor->email];
            Mail::to($data['buyer_email'])
                ->cc($vendorCopy)
                ->send(new BuyerPrivateInvitationMail($vendor, $link, $data['buyer_name']));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['buyer_email' => 'The invitation could not be sent just now. Please try again in a moment.']);
        }

        return back()->with('status', 'Private invitation sent to '.$data['buyer_name'].' at '.$data['buyer_email'].'. A copy is in your inbox.');
    }

    public function show(string $token)
    {
        $link = $this->resolveLink($token);

        return response()->view('vendor-buyer-leads.capture', [
            'link' => $link,
            'sections' => BuyerQualificationQuestionnaire::sections(),
            'returnToName' => (string) ($link->vendor->name ?: $link->vendor->company),
            'returnToCompany' => (string) ($link->vendor->company ?: $link->vendor->name),
            'returnToEmail' => (string) $link->vendor->email,
        ])
            ->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }

    /**
     * Accept a completed GASQ Security Services Opportunity Qualification Form.
     *
     * The submission is attributed to the vendor who owns the invitation link, scored
     * for internal review, and emailed to that vendor with GASQ copied. Mail failures
     * are logged rather than surfaced: the lead is already saved, and losing a buyer's
     * answers to a transient SMTP problem is the worse outcome.
     */
    public function store(Request $request, string $token)
    {
        $link = $this->resolveLink($token);
        $data = $request->validate($this->rules($request), [], BuyerQualificationQuestionnaire::answerAttributes());

        $startDate = Carbon::parse($data['start_date']);
        $answers = BuyerQualificationQuestionnaire::normalizeAnswers($data['answers']);
        $serviceTypes = array_map(
            fn (string $type): string => BuyerQualificationQuestionnaire::SERVICE_TYPES[$type],
            $data['service_types']
        );

        $lead = VendorBuyerLead::create([
            'vendor_id' => $link->vendor_id,
            'status' => 'new',
            'contact_name' => $data['contact_name'],
            'contact_title' => $data['contact_title'],
            'company' => $data['company'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'site_address' => $data['site_address'],
            'decision_maker_name' => $data['decision_maker_name'],
            'decision_maker_phone' => $data['decision_maker_phone'],
            'decision_maker_email' => $data['decision_maker_email'],
            'approved_budget' => $data['approved_budget'],
            'approved_budget_basis' => $data['approved_budget_basis'],
            'service_type' => implode(', ', $serviceTypes),
            'service_type_other' => in_array('other', $data['service_types'], true) ? ($data['service_type_other'] ?? null) : null,
            'industry' => $data['industry'],
            'preferred_contact_method' => $data['preferred_contact_method'],
            'scope' => $data['scope'],
            'start_date' => $startDate->toDateString(),
            'service_end_date' => $data['service_end_date'],
            'hours_per_day' => $data['hours_per_day'],
            'days_per_week' => $data['days_per_week'],
            'officers_per_shift' => $data['officers_per_shift'],
            'number_of_weeks' => $data['number_of_weeks'],
            'weekly_hours' => round((float) $data['hours_per_day'] * (float) $data['days_per_week'] * (int) $data['officers_per_shift'], 2),
            'qualification_answers' => $answers,
            'qualification_status' => BuyerQualificationQuestionnaire::deriveStatus($answers, $startDate),
            'qualification_submitted_at' => now(),
        ]);

        $this->sendSubmission($lead);

        return redirect()->route('buyer-enquiries.show', ['token' => $token])->with('enquiry_received', true);
    }

    public function update(Request $request, VendorBuyerLead $lead)
    {
        abort_unless($request->user()->isVendor() && $lead->vendor_id === $request->user()->id, 404);
        $lead->update($request->validate([
            'status' => ['required', Rule::in(array_keys(VendorBuyerLead::STATUSES))],
            'qualification_status' => ['nullable', Rule::in(array_keys(BuyerQualificationQuestionnaire::STATUSES))],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]));

        return back()->with('status', 'Lead updated.');
    }

    /**
     * Every field on the printed form is required, so the vendor never has to chase a
     * half-answered qualification. The two exceptions are conditional: the free-text
     * service type only when "Other" is selected, and questions 19 and 22, which the
     * form itself allows to be answered "N/A".
     */
    private function rules(Request $request): array
    {
        $serviceTypes = (array) $request->input('service_types', []);

        return array_merge([
            'company' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_title' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'site_address' => ['required', 'string', 'max:500'],
            'decision_maker_name' => ['required', 'string', 'max:255'],
            'decision_maker_phone' => ['required', 'string', 'max:50'],
            'decision_maker_email' => ['required', 'email', 'max:255'],
            'approved_budget' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'approved_budget_basis' => ['required', Rule::in(array_keys(BuyerQualificationQuestionnaire::BUDGET_BASIS))],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'service_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'hours_per_day' => ['required', 'numeric', 'gt:0', 'max:24'],
            'days_per_week' => ['required', 'numeric', 'gt:0', 'max:7'],
            'officers_per_shift' => ['required', 'integer', 'min:1', 'max:500'],
            'number_of_weeks' => ['required', 'integer', 'min:1', 'max:520'],
            'service_types' => ['required', 'array', 'min:1'],
            'service_types.*' => [Rule::in(array_keys(BuyerQualificationQuestionnaire::SERVICE_TYPES))],
            'service_type_other' => [Rule::requiredIf(in_array('other', $serviceTypes, true)), 'nullable', 'string', 'max:100'],
            'industry' => ['required', 'string', 'max:255'],
            'preferred_contact_method' => ['required', Rule::in(array_keys(BuyerQualificationQuestionnaire::CONTACT_METHODS))],
            'scope' => ['required', 'string', 'max:10000'],
            // Shown on the form so the buyer can see where their answers go. The value
            // is never used for delivery: the invitation link decides the vendor, so a
            // tampered field cannot redirect a submission to an arbitrary address.
            'return_to_email' => ['nullable', 'email', 'max:255'],
            'answers' => ['required', 'array'],
        ], BuyerQualificationQuestionnaire::answerRules());
    }

    /**
     * Return the completed form to both parties.
     *
     * The vendor named on the invitation link gets the working copy, with GASQ copied;
     * the buyer gets a receipt of their own answers. The two are sent independently so
     * a bounce on one address still delivers the other, and either failing is logged
     * rather than shown: the lead is already saved and the vendor can see it.
     */
    private function sendSubmission(VendorBuyerLead $lead): void
    {
        $vendorEmail = $lead->vendor?->email;

        if ($vendorEmail) {
            try {
                Mail::to($vendorEmail)
                    ->cc($this->copiesFor($vendorEmail, [$vendorEmail, $lead->email]))
                    ->send(new BuyerQualificationSubmittedMail($lead));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            Mail::to($lead->email)->send(new BuyerQualificationSubmittedMail($lead, forBuyer: true));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * The GASQ copy address, dropped when it is already one of the named recipients.
     *
     * @param  array<int, string|null>  $alreadyAddressed
     * @return array<int, string>
     */
    private function copiesFor(string $primary, array $alreadyAddressed = []): array
    {
        $copyTo = (string) config('services.gasq.qualification_form_cc');
        if (! filter_var($copyTo, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        $taken = array_map(
            static fn (?string $address): string => strtolower(trim((string) $address)),
            array_merge([$primary], $alreadyAddressed)
        );

        return in_array(strtolower($copyTo), $taken, true) ? [] : [$copyTo];
    }

    private function resolveLink(string $token): VendorLeadLink
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);

        return VendorLeadLink::where('token', $token)->whereHas('vendor', fn ($query) => $query->where('user_type', 'vendor'))->firstOrFail();
    }
}
