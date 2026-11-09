<?php

namespace App\Http\Controllers;

use App\Models\VendorBuyerLead;
use App\Models\VendorLeadLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VendorBuyerLeadController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isVendor(), 403);
        $filters = $request->validate(['status' => ['nullable', Rule::in(array_keys(VendorBuyerLead::STATUSES))], 'q' => ['nullable', 'string', 'max:100']]);
        $link = VendorLeadLink::firstOrCreate(['vendor_id' => $request->user()->id], ['token' => Str::random(64)]);
        $leads = VendorBuyerLead::where('vendor_id', $request->user()->id)->withCount('estimates')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
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

    public function show(string $token)
    {
        $link = $this->resolveLink($token);

        return response()->view('vendor-buyer-leads.capture', compact('link'))
            ->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer')->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, string $token)
    {
        $link = $this->resolveLink($token);
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'site_address' => ['required', 'string', 'max:500'],
            'service_type' => ['required', 'string', 'max:100'],
            'scope' => ['required', 'string', 'max:10000'],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'weekly_hours' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
        ]);
        VendorBuyerLead::create(array_merge($data, ['vendor_id' => $link->vendor_id, 'status' => 'new']));

        return redirect()->route('buyer-enquiries.show', ['token' => $token])->with('enquiry_received', true);
    }

    public function update(Request $request, VendorBuyerLead $lead)
    {
        abort_unless($request->user()->isVendor() && $lead->vendor_id === $request->user()->id, 404);
        $lead->update($request->validate(['status' => ['required', Rule::in(array_keys(VendorBuyerLead::STATUSES))], 'notes' => ['nullable', 'string', 'max:10000']]));

        return back()->with('status', 'Lead updated.');
    }

    private function resolveLink(string $token): VendorLeadLink
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);

        return VendorLeadLink::where('token', $token)->whereHas('vendor', fn ($query) => $query->where('user_type', 'vendor'))->firstOrFail();
    }
}
