{{--
    Shared Lead/Offer summary block.
    Used by the vendor invitation email, the vendor dashboard detail card,
    and the buyer/admin dashboard summary card so all three surfaces stay
    in lockstep.

    Required vars:
        $invitation OR $opportunity OR $job
        $redacted (bool) — true for vendor-facing surfaces before they accept;
                           false for buyer/admin and post-accept vendor views.

    Optional vars:
        $isHtmlEmail (bool) — when true, uses inline styles for email-safe rendering
        $showScope (bool, default true) — render "Full Project Scope of Work Details"

    Style note: every value cell uses neutral fonts + bold labels so this
    renders consistently in dompdf (if ever reused there) and inline-styled email.
--}}
@php
    $opportunity = $opportunity ?? $invitation?->opportunity ?? null;
    $job = $job ?? $opportunity?->jobPosting ?? null;
    $buyer = $job?->user ?? null;
    $questionnaire = is_array($job?->questionnaire_data) ? $job->questionnaire_data : [];

    $redacted = $redacted ?? true;
    $isHtmlEmail = $isHtmlEmail ?? false;
    $showScope = $showScope ?? true;

    // Identity / verification
    $decisionMakerVerified = (bool) ($opportunity?->decision_maker_verified ?? false);
    $budgetVerified = (bool) ($opportunity?->budget_confirmed ?? false);
    $phoneVerified = (bool) ($buyer?->phone_verified ?? false);

    // Redactor for vendor-facing pre-accept views.
    $maskName = function ($name) use ($redacted) {
        $name = trim((string) $name);
        if ($name === '') return '—';
        if (! $redacted) return $name;
        $parts = preg_split('/\s+/', $name) ?: [$name];
        $first = array_shift($parts);
        $first = strlen($first) > 1 ? substr($first, 0, 1) . str_repeat('*', max(0, strlen($first) - 1)) : $first;
        $last = $parts ? array_pop($parts) : '';
        $last = $last !== '' ? str_repeat('*', max(1, strlen($last) - 1)) . substr($last, -1) : '';
        return trim($first . ' ' . $last);
    };
    $maskEmail = function ($email) use ($redacted) {
        $email = trim((string) $email);
        if ($email === '' || ! str_contains($email, '@')) return '—';
        if (! $redacted) return $email;
        [$local, $domain] = explode('@', $email, 2);
        $localMasked = strlen($local) <= 2
            ? substr($local, 0, 1) . '*'
            : substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 2)) . substr($local, -1);
        $domainParts = explode('.', $domain);
        $tld = array_pop($domainParts);
        $rootMasked = implode('.', array_map(fn($p) => substr($p, 0, 1) . str_repeat('*', max(1, strlen($p) - 1)), $domainParts));
        return $localMasked . '@' . $rootMasked . '.' . $tld;
    };
    $maskPhone = function ($phone) use ($redacted) {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (strlen($digits) < 10) return '—';
        $area = substr($digits, -10, 3);
        return $redacted ? "({$area}) ***-****" : '(' . $area . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    };

    // Buyer identity (redacted or not based on $redacted)
    $decisionMakerName = $maskName($buyer?->name);
    $emailDisplay = $maskEmail($buyer?->email);
    $phoneDisplay = $maskPhone($buyer?->phone);

    // Location
    $city = $buyer?->city ?? '';
    $state = $buyer?->state ?? '';
    $zip = $job?->zip_code ?? '';
    $rawLocation = trim((string) ($job?->location ?? ''));
    if ($city === '' || $state === '') {
        // Try to parse from location string like "..., Atlanta, GA 30331, USA"
        if ($rawLocation !== '' && preg_match('/,\s*([^,]+),\s*([A-Z]{2})\s*(\d{5})?/', $rawLocation, $m)) {
            $city = $city ?: trim($m[1]);
            $state = $state ?: trim($m[2]);
            $zip = $zip ?: ($m[3] ?? '');
        }
    }
    $usStates = [
        'ALABAMA' => 'AL','ALASKA' => 'AK','ARIZONA' => 'AZ','ARKANSAS' => 'AR','CALIFORNIA' => 'CA',
        'COLORADO' => 'CO','CONNECTICUT' => 'CT','DELAWARE' => 'DE','FLORIDA' => 'FL','GEORGIA' => 'GA',
        'HAWAII' => 'HI','IDAHO' => 'ID','ILLINOIS' => 'IL','INDIANA' => 'IN','IOWA' => 'IA',
        'KANSAS' => 'KS','KENTUCKY' => 'KY','LOUISIANA' => 'LA','MAINE' => 'ME','MARYLAND' => 'MD',
        'MASSACHUSETTS' => 'MA','MICHIGAN' => 'MI','MINNESOTA' => 'MN','MISSISSIPPI' => 'MS','MISSOURI' => 'MO',
        'MONTANA' => 'MT','NEBRASKA' => 'NE','NEVADA' => 'NV','NEW HAMPSHIRE' => 'NH','NEW JERSEY' => 'NJ',
        'NEW MEXICO' => 'NM','NEW YORK' => 'NY','NORTH CAROLINA' => 'NC','NORTH DAKOTA' => 'ND','OHIO' => 'OH',
        'OKLAHOMA' => 'OK','OREGON' => 'OR','PENNSYLVANIA' => 'PA','RHODE ISLAND' => 'RI','SOUTH CAROLINA' => 'SC',
        'SOUTH DAKOTA' => 'SD','TENNESSEE' => 'TN','TEXAS' => 'TX','UTAH' => 'UT','VERMONT' => 'VT',
        'VIRGINIA' => 'VA','WASHINGTON' => 'WA','WEST VIRGINIA' => 'WV','WISCONSIN' => 'WI','WYOMING' => 'WY',
        'DISTRICT OF COLUMBIA' => 'DC',
    ];
    $upperLocation = strtoupper($rawLocation);
    $locationIsStateOnly = isset($usStates[$upperLocation]) || in_array($upperLocation, $usStates, true);
    if ($state === '' && $rawLocation !== '') {
        if (isset($usStates[$upperLocation])) {
            $state = $usStates[$upperLocation];
        } elseif (in_array($upperLocation, $usStates, true)) {
            $state = $upperLocation;
        }
    }
    if ($city === '' && $rawLocation !== '' && ! $locationIsStateOnly) {
        // location wasn't just a state name — use it as the city fallback
        $city = $rawLocation;
    }

    // Scope: hours, days, weeks, staff
    $hoursPerDay = (float) data_get($questionnaire, 'hours_per_day', 24);
    $daysPerWeek = (float) data_get($questionnaire, 'days_per_week', 7);
    $weeksPerYear = (float) data_get($questionnaire, 'weeks_per_year', 52);
    $staffPerShift = (float) data_get($questionnaire, 'staff_per_shift', $job?->guards_per_shift ?? 1);
    if ($staffPerShift <= 0) $staffPerShift = max(1, (int) ($job?->guards_per_shift ?? 1));

    $weeklyHours = $hoursPerDay * $daysPerWeek;
    $monthlyHours = (int) round(($weeklyHours * $weeksPerYear) / 12);
    $monthsPerYear = max(1, (int) round($weeksPerYear / 4.333));
    $annualHours = $hoursPerDay * $daysPerWeek * $weeksPerYear;
    $totalStaff = max(1, (int) round(($annualHours / 1456) * $staffPerShift / max(1, $staffPerShift)));
    // Estimated staff to deliver coverage given billable hours per FTE (1,456)
    $totalStaff = max(1, (int) ceil(($annualHours * $staffPerShift) / 1456));

    // Contract value
    $contractValue = (float) ($opportunity?->estimated_annual_contract_value ?? 0);
    $contractValueFormatted = $contractValue > 0 ? '$' . number_format($contractValue, 2) : '—';

    // Dates
    $startDate = $job?->service_start_date?->format('m/d/y') ?? '—';
    $closeOutCarbon = $job?->service_end_date;
    if (! $closeOutCarbon && $job?->service_start_date && $weeksPerYear > 0) {
        $closeOutCarbon = $job->service_start_date->copy()->addWeeks((int) $weeksPerYear);
    }
    $closeOutDate = $closeOutCarbon?->format('m/d/y') ?? '—';

    // Acceptance progress
    $maxAccepts = max(1, (int) ($opportunity?->max_accepts ?? 5));
    $acceptedCount = $opportunity
        ? (int) $opportunity->invitations()->whereIn('status', ['accepted', 'bid_submitted'])->count()
        : 0;
    $creditsToUnlock = (int) ($invitation?->credits_to_unlock ?? 0);

    // Scope detail questions
    $serviceFor = data_get($questionnaire, 'request_type', 'New Purchase of Services');
    $decisionMaker = data_get($questionnaire, 'final_decision_maker', '—');
    $priceShopping = data_get($questionnaire, 'price_shopping_status', 'No');
    $hiringIntent = data_get($questionnaire, 'hiring_decision_likelihood', '—');

    // Service type label
    $serviceType = $job?->category ?? data_get($questionnaire, 'service_types.0', 'Security Services');
@endphp

@if($isHtmlEmail)
    @php
        $rowStyle = 'padding:5px 8px;border-bottom:1px solid #e5e7eb;font-size:11.5px;';
        $labelStyle = 'color:#4b5563;font-weight:600;';
    @endphp
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
        <tr><td colspan="4" style="background:#fde2e2;color:#7f1d1d;padding:10px 12px;font-size:14px;font-weight:700;border:2px solid #b91c1c;text-align:center;">
            🚨 ALERT! New Security Opportunity Project in {{ $city ?: ($job?->location ?? 'Location TBD') }}@if($state), {{ $state }}@endif – Contract Value: {{ $contractValueFormatted }}
        </td></tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Type of Service Requested:</span></td>
            <td style="{{ $rowStyle }}" colspan="3">{{ $serviceType }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Decision Maker Verified:</span></td>
            <td style="{{ $rowStyle }}">{{ $decisionMakerVerified ? 'Yes' : 'Pending' }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Decision Maker Name:</span></td>
            <td style="{{ $rowStyle }}">{{ $decisionMakerName }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Email Address:</span></td>
            <td style="{{ $rowStyle }}">{{ $emailDisplay }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Phone Number Verified:</span></td>
            <td style="{{ $rowStyle }}">{{ $phoneDisplay }}{{ $phoneVerified ? ' ✓' : '' }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Approved Budget Verified:</span></td>
            <td style="{{ $rowStyle }}">{{ $budgetVerified ? 'Yes' : 'Pending' }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Bid Offer Value:</span></td>
            <td style="{{ $rowStyle }}"><strong>{{ $contractValueFormatted }}</strong></td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Project Location — City:</span></td>
            <td style="{{ $rowStyle }}">{{ $city ?: '—' }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">State:</span></td>
            <td style="{{ $rowStyle }}">{{ $state ?: '—' }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Zip Code:</span></td>
            <td style="{{ $rowStyle }}" colspan="3">{{ $zip ?: '—' }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Hours of Coverage:</span></td>
            <td style="{{ $rowStyle }}">{{ (int) $hoursPerDay }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Days of Coverage:</span></td>
            <td style="{{ $rowStyle }}">{{ (int) $daysPerWeek }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Weekly Hours Hired to Work:</span></td>
            <td style="{{ $rowStyle }}">{{ number_format($weeklyHours) }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Monthly Hours Hired to Work:</span></td>
            <td style="{{ $rowStyle }}">{{ number_format($monthlyHours) }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Number of Weeks:</span></td>
            <td style="{{ $rowStyle }}">{{ (int) $weeksPerYear }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Number of Months:</span></td>
            <td style="{{ $rowStyle }}">{{ $monthsPerYear }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Staff Required:</span></td>
            <td style="{{ $rowStyle }}">{{ $totalStaff }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Term/Annual Hours:</span></td>
            <td style="{{ $rowStyle }}">{{ number_format($annualHours) }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Estimated Start Date:</span></td>
            <td style="{{ $rowStyle }}">{{ $startDate }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Estimated Close-Out Date:</span></td>
            <td style="{{ $rowStyle }}">{{ $closeOutDate }}</td>
        </tr>
        <tr>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Total Credits to respond:</span></td>
            <td style="{{ $rowStyle }}">{{ number_format($creditsToUnlock) }}</td>
            <td style="{{ $rowStyle }}"><span style="{{ $labelStyle }}">Responses:</span></td>
            <td style="{{ $rowStyle }}">{{ $acceptedCount }}/{{ $maxAccepts }} Professionals have accepted bid offer</td>
        </tr>
    </table>
@else
    {{-- Bootstrap version for in-app dashboards --}}
    <div class="card border-danger mb-3">
        <div class="card-header bg-danger text-white fw-bold text-center">
            🚨 ALERT! New Security Opportunity Project in {{ $city ?: ($job?->location ?? 'Location TBD') }}@if($state), {{ $state }}@endif – Contract Value: {{ $contractValueFormatted }}
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td class="text-muted" style="width:25%"><strong>Type of Service Requested:</strong></td><td colspan="3">{{ $serviceType }}</td></tr>
                    <tr>
                        <td class="text-muted"><strong>Decision Maker Verified:</strong></td>
                        <td>{{ $decisionMakerVerified ? 'Yes' : 'Pending' }}</td>
                        <td class="text-muted"><strong>Decision Maker Name:</strong></td>
                        <td>{{ $decisionMakerName }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Email Address:</strong></td>
                        <td>{{ $emailDisplay }}</td>
                        <td class="text-muted"><strong>Phone Number Verified:</strong></td>
                        <td>{{ $phoneDisplay }}{{ $phoneVerified ? ' ✓' : '' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Approved Budget Verified:</strong></td>
                        <td>{{ $budgetVerified ? 'Yes' : 'Pending' }}</td>
                        <td class="text-muted"><strong>Total Bid Offer Value:</strong></td>
                        <td><strong>{{ $contractValueFormatted }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Project Location — City:</strong></td>
                        <td>{{ $city ?: '—' }}</td>
                        <td class="text-muted"><strong>State:</strong></td>
                        <td>{{ $state ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Zip Code:</strong></td>
                        <td colspan="3">{{ $zip ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Total Hours of Coverage:</strong></td>
                        <td>{{ (int) $hoursPerDay }}</td>
                        <td class="text-muted"><strong>Total Days of Coverage:</strong></td>
                        <td>{{ (int) $daysPerWeek }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Total Weekly Hours Hired to Work:</strong></td>
                        <td>{{ number_format($weeklyHours) }}</td>
                        <td class="text-muted"><strong>Total Monthly Hours Hired to Work:</strong></td>
                        <td>{{ number_format($monthlyHours) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Total Number of Weeks:</strong></td>
                        <td>{{ (int) $weeksPerYear }}</td>
                        <td class="text-muted"><strong>Total Number of Months:</strong></td>
                        <td>{{ $monthsPerYear }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Total Staff Required:</strong></td>
                        <td>{{ $totalStaff }}</td>
                        <td class="text-muted"><strong>Total Term/Annual Hours:</strong></td>
                        <td>{{ number_format($annualHours) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Estimated Start Date:</strong></td>
                        <td>{{ $startDate }}</td>
                        <td class="text-muted"><strong>Estimated Close-Out Date:</strong></td>
                        <td>{{ $closeOutDate }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><strong>Total Credits to respond:</strong></td>
                        <td>{{ number_format($creditsToUnlock) }}</td>
                        <td class="text-muted"><strong>Responses:</strong></td>
                        <td>{{ $acceptedCount }}/{{ $maxAccepts }} Professionals have accepted</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if($showScope)
        <div class="card border-secondary mb-3">
            <div class="card-header bg-light fw-bold">Full Project Scope of Work Details</div>
            <div class="card-body small">
                <ol class="mb-0">
                    <li><strong>What is this service for?</strong>
                        <div class="ms-3">{{ str_replace('_', ' ', ucfirst((string) $serviceFor)) }}</div></li>
                    <li class="mt-2"><strong>Are you the person authorized to make a final buying commitment with the vendor or approve payment for the proposed services?</strong>
                        <div class="ms-3">{{ ucfirst((string) $decisionMaker) }}</div></li>
                    <li class="mt-2"><strong>Are you price shopping?</strong>
                        <div class="ms-3">{{ $priceShopping }}</div></li>
                    <li class="mt-2"><strong>How likely are you to make a hiring decision?</strong>
                        <div class="ms-3">{{ $hiringIntent }}</div></li>
                </ol>
            </div>
        </div>
    @endif
@endif
