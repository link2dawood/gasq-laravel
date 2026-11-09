@extends('layouts.app')
@section('title', $estimate->exists ? 'Manage Estimate' : 'Prepare Estimate')
@section('header_variant', 'dashboard')
@section('content')
<div class="container py-4" style="max-width:1050px;">
    <p><a href="{{ route('vendor-estimates.index') }}">Estimate dashboard</a> · <a href="{{ route('vendor-buyer-leads.index') }}">Buyer enquiries</a></p>
    <h1>{{ $estimate->exists ? 'Manage estimate' : 'Prepare estimate' }}</h1>
    @if($estimate->exists)<p class="text-muted">{{ $estimate->number }}</p>@endif
    @if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="border rounded p-3 mb-4">
        <h2 class="h5">Buyer submission</h2>
        <p><strong>{{ $lead->contact_name }}</strong>{{ $lead->company ? ' · '.$lead->company : '' }}<br>{{ $lead->email }} · {{ $lead->phone }}<br>{{ $lead->site_address }}</p>
        <p><strong>Service:</strong> {{ $lead->service_type }}<br><strong>Preferred start:</strong> {{ $lead->start_date?->format('M j, Y') ?? 'Not supplied' }}<br><strong>Requested weekly hours:</strong> {{ $lead->weekly_hours ?? 'Not supplied' }}</p>
        <div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $lead->scope }}</div>
    </section>
    <form method="POST" action="{{ $estimate->exists ? route('vendor-estimates.update', $estimate) : route('vendor-estimates.store', $lead) }}">
        @csrf @if($estimate->exists) @method('PATCH') @endif
        <h2 class="h4">Estimate details</h2>
        <div class="mb-3"><label class="form-label" for="title">Estimate title *</label><input class="form-control" id="title" name="title" maxlength="255" value="{{ old('title', $estimate->title) }}" required></div>
        <div class="mb-3"><label class="form-label" for="scope">Proposed scope of services *</label><textarea class="form-control" id="scope" name="scope" rows="5" maxlength="10000" required>{{ old('scope', $estimate->scope) }}</textarea></div>
        <p class="text-muted">Enter pricing in {{ $estimate->currency }}. Weekly hours are the total billable service hours across all personnel. Monthly costs are annual recurring costs divided by 12.</p>
        <div class="row g-3 mb-3">
            @foreach(['hourly_rate' => ['Hourly bill rate', '0.01', '100000', '0.01'], 'weekly_hours' => ['Weekly service hours', '0.01', '100000', '0.01'], 'weeks_per_year' => ['Service weeks per year', '1', '52', '1'], 'one_time_cost' => ['One-time cost', '0', '100000000', '0.01']] as $field => [$label, $min, $max, $step])
            <div class="col-md-6"><label class="form-label" for="{{ $field }}">{{ $label }} *</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" type="number" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" value="{{ old($field, $estimate->$field) }}" required></div>
            @endforeach
            <div class="col-md-6"><label class="form-label" for="valid_until">Valid through *</label><input class="form-control" id="valid_until" name="valid_until" type="date" value="{{ old('valid_until', $estimate->valid_until?->toDateString()) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="status">Estimate status *</label><select class="form-select" id="status" name="status">@foreach(\App\Models\VendorLeadEstimate::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $estimate->status) === $value)>{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="mb-3"><label class="form-label" for="terms">Terms and assumptions</label><textarea class="form-control" id="terms" name="terms" rows="4" maxlength="10000">{{ old('terms', $estimate->terms) }}</textarea></div>
        <button type="submit" class="btn btn-primary">Save estimate</button>
    </form>
    @if($estimate->exists)
    <section class="border rounded p-3 mt-4">
        <h2 class="h5">Saved estimate summary · {{ $estimate->currency }}</h2>
        <table class="table"><tbody>
            @foreach(['weekly' => 'Weekly recurring cost', 'monthly' => 'Average monthly recurring cost', 'annual' => 'Annual recurring cost', 'first_year' => 'First-year total including one-time cost'] as $key => $label)<tr><th>{{ $label }}</th><td class="text-end">{{ $estimate->money($estimate->totals()[$key]) }}</td></tr>@endforeach
        </tbody></table>
        <p class="small text-muted">Save changes to refresh these totals. The PDF uses the saved scope and pricing.</p>
        @if($estimate->status === 'ready' && !$estimate->valid_until->isBefore(today()))
        <h3 class="h5">Download protected PDF</h3>
        <form method="POST" action="{{ route('vendor-estimates.download', $estimate) }}">@csrf
            <label class="form-label" for="pdf_password">PDF password (8–32 characters)</label>
            <input type="password" class="form-control mb-2" id="pdf_password" name="pdf_password" minlength="8" maxlength="32" autocomplete="new-password" required>
            <p class="small text-muted">Choose a password to open this PDF. It is not saved or included in the document. Share it with the recipient separately.</p>
            <button class="btn btn-outline-primary" type="submit">Download password-protected PDF</button>
        </form>
        <hr class="my-4">
        <h3 class="h5">Email estimate to buyer</h3>
        <p>Send to <strong>{{ $estimate->buyer['email'] }}</strong> with a protected PDF and a private scheduling link. Password access remains restricted until you release it after the appointment review.</p>
        @if($canEmailEstimate ?? false)
        <form method="POST" action="{{ route('vendor-estimates.send', $estimate) }}">@csrf
            <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <label class="form-label" for="email-pdf-password">Password for the attached PDF (8–32 characters)</label>
            <input type="password" class="form-control mb-2" id="email-pdf-password" name="pdf_password" minlength="8" maxlength="32" autocomplete="new-password" required>
            <p class="small text-muted">Remember this password. The buyer can retrieve it only after you confirm the appointment review and manually release access. Sending creates a snapshot of the saved estimate.</p>
            <button type="submit" class="btn btn-primary">Send estimate to buyer</button>
        </form>
        @else
        <p class="text-muted"><a href="{{ route('vendor-calendar.index') }}">Enable your GASQ calendar</a> or configure the Bookafy or Calendly appraisal booking URL to enable email delivery with a scheduling link.</p>
        @endif
        @else
        <p class="mb-0">Mark the estimate Ready and set a current validity date to download its protected PDF.</p>
        @endif
    </section>
    <section class="mt-4">
        <h2 class="h4">Email delivery history</h2>
        <p class="small text-muted">Sent means the mail transport accepted the email; inbox delivery is not confirmed. Check any “Needs delivery review” attempt before sending again. A scheduling link opening may also come from an email scanner.</p>
        @forelse($deliveries as $delivery)
        <article class="border rounded p-3 mb-2">
            <strong>{{ \App\Models\VendorEstimateDelivery::STATUSES[$delivery->status] }}</strong>
            <p class="mb-1">To {{ $delivery->recipient }} · Requested {{ $delivery->created_at->format('M j, Y g:i A') }}</p>
            @if($delivery->sent_at)<p class="mb-1">Transport accepted {{ $delivery->sent_at->format('M j, Y g:i A') }}</p>@endif
            @if($delivery->failure_code)<p class="mb-1">Delivery detail: {{ $delivery->failure_code }}</p>@endif
            @if($delivery->scheduling_opened_at)<p class="mb-1">Scheduling link opened {{ $delivery->scheduling_opened_at->format('M j, Y g:i A') }}</p>@endif
            <p class="mt-2"><a href="{{ $delivery->accessUrl() }}" target="_blank" rel="noopener noreferrer">Buyer PDF access page</a></p>
            @if($delivery->access_revoked_at)
                <p>Online access revoked {{ $delivery->access_revoked_at->utc()->format('M j, Y g:i A') }} UTC.</p>
            @elseif($delivery->password_released_at)
                <p>Password released {{ $delivery->password_released_at->utc()->format('M j, Y g:i A') }} UTC by vendor #{{ $delivery->password_released_by }}. Access expires {{ $delivery->access_expires_at->utc()->format('M j, Y g:i A') }} UTC.</p>
            @elseif($delivery->status === 'sent' && $delivery->password_hash)
                @php($eligibleReviews = $reviews->filter(fn ($review) => $review->buyer_email === $delivery->recipient && $review->ends_at->gte($delivery->created_at)))
                @if($eligibleReviews->isNotEmpty())
                <details><summary>Release PDF password after review</summary>
                <form method="POST" action="{{ route('vendor-pdf.release', $delivery) }}" class="mt-3">@csrf
                    <label class="form-label" for="review-{{ $delivery->id }}">Completed appointment</label><select class="form-select mb-2" name="appointment_id" id="review-{{ $delivery->id }}">@foreach($eligibleReviews as $review)<option value="{{ $review->id }}">{{ $review->starts_at->setTimezone($review->timezone)->format('M j, Y g:i A T') }} — {{ $review->buyer_name }}</option>@endforeach</select>
                    <label class="form-label" for="release-password-{{ $delivery->id }}">Original password for this PDF</label><input class="form-control mb-2" type="password" name="pdf_password" id="release-password-{{ $delivery->id }}" autocomplete="off" minlength="8" maxlength="32" required>
                    <label class="mb-2"><input type="checkbox" name="review_completed" value="1" required> I confirm the review took place with the buyer and authorized budget decision-maker.</label><br>
                    <button class="btn btn-primary btn-sm" type="submit">Release password access</button>
                </form></details>
                @elseif(!$delivery->native_scheduling)
                <details><summary>Record completed external review and release access</summary>
                <form class="mt-3" method="POST" action="{{ route('vendor-pdf.release', $delivery) }}">@csrf
                    <p class="small text-muted">Record the actual completed Bookafy/Calendly review. Times below must include a UTC offset, for example 2026-11-08T14:00:00-05:00. This is your attestation, not an automated provider confirmation.</p>
                    <label class="form-label">Appointment reference<input class="form-control mb-2" name="external_review_reference" maxlength="255" required></label>
                    <label class="form-label">Review started (with UTC offset)<input class="form-control mb-2" name="external_review_started_at" placeholder="2026-11-08T14:00:00-05:00" required></label>
                    <label class="form-label">Review ended (with UTC offset)<input class="form-control mb-2" name="external_review_ended_at" placeholder="2026-11-08T14:30:00-05:00" required></label>
                    <label class="form-label">Original PDF password<input class="form-control mb-2" type="password" name="pdf_password" autocomplete="off" minlength="8" maxlength="32" required></label>
                    <label><input type="checkbox" name="review_completed" value="1" required> I confirm this review took place with the buyer and authorized decision-maker.</label><br>
                    <button class="btn btn-primary btn-sm mt-2" type="submit">Record review &amp; release access</button>
                </form></details>
                @else<p class="small text-muted">Password restricted. A completed, uncanceled appointment review is required before release.</p>@endif
            @elseif(!$delivery->password_hash)<p class="small text-muted">This older PDF has no password verifier. Prepare and send a new delivery to use controlled release.</p>
            @endif
            @unless($delivery->access_revoked_at)
            <form class="mt-2" method="POST" action="{{ route('vendor-pdf.revoke', $delivery) }}">@csrf<button class="btn btn-sm btn-outline-danger" type="submit">Revoke online PDF access</button></form>
            @endunless

        </article>
        @empty
        <p class="text-muted">No email delivery attempts yet.</p>
        @endforelse
        {{ $deliveries->links() }}
    </section>
    <section class="mt-4"><h2 class="h4">Protected PDF access history</h2>
    <p class="small text-muted">Times are UTC. Records cover app activity and identify each PDF by its checksum. Opens of emailed or downloaded PDFs outside the website cannot be observed.</p>
    <div class="table-responsive"><table class="table"><thead><tr><th>Time (UTC)</th><th>Event</th><th>Delivery / actor</th><th>PDF checksum</th></tr></thead><tbody>
    @forelse($accessHistory as $event)<tr><td>{{ $event->created_at->utc()->format('M j, Y H:i:s') }}</td><td>{{ str_replace('_', ' ', $event->event) }}</td><td>{{ $event->vendor_estimate_delivery_id ?? 'Vendor download' }} / {{ $event->actor_id ?? 'Buyer or system' }}</td><td><small title="{{ $event->pdf_sha256 }}">{{ substr($event->pdf_sha256 ?? '', 0, 12) }}</small></td></tr>@empty<tr><td colspan="4">No protected PDF activity recorded yet.</td></tr>@endforelse
    </tbody></table></div>{{ $accessHistory->links() }}
    </section>
    @endif
</div>
@endsection
