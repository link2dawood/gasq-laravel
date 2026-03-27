@extends('layouts.app')

@section('title', 'Post Coverage Schedule')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="h2 mb-2">Post Coverage Schedule</h1>
        <p class="text-gasq-muted mb-0" style="max-width: 900px; margin-inline: auto;">
            Comprehensive security coverage options with transparent pricing (static UI preview).
        </p>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Post Coverage Schedule</th>
                    <th class="text-center">Weekly Hours</th>
                    <th class="text-center">Minimum Heads</th>
                    <th class="text-end">Weekly Billing</th>
                    <th class="text-end">Monthly Billing</th>
                    <th class="text-end">Annual Billing</th>
                    <th class="text-end">Cost per Guard Annual</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-semibold">8 hrs/day, Mon - Fri</td>
                    <td class="text-center fw-semibold">40</td>
                    <td class="text-center fw-semibold">2</td>
                    <td class="text-end font-monospace">$1,890.00</td>
                    <td class="text-end font-monospace">$8,183.70</td>
                    <td class="text-end font-monospace">$98,280.00</td>
                    <td class="text-end font-monospace">$49,140.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">8 hrs/day, 7 days</td>
                    <td class="text-center fw-semibold">56</td>
                    <td class="text-center fw-semibold">2</td>
                    <td class="text-end font-monospace">$2,646.00</td>
                    <td class="text-end font-monospace">$11,457.18</td>
                    <td class="text-end font-monospace">$137,592.00</td>
                    <td class="text-end font-monospace">$68,796.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">12 hrs/day, Mon - Fri</td>
                    <td class="text-center fw-semibold">60</td>
                    <td class="text-center fw-semibold">3</td>
                    <td class="text-end font-monospace">$2,835.00</td>
                    <td class="text-end font-monospace">$12,275.55</td>
                    <td class="text-end font-monospace">$147,420.00</td>
                    <td class="text-end font-monospace">$49,140.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">70 hrs/week Coverage</td>
                    <td class="text-center fw-semibold">70</td>
                    <td class="text-center fw-semibold">3</td>
                    <td class="text-end font-monospace">$3,307.50</td>
                    <td class="text-end font-monospace">$14,321.48</td>
                    <td class="text-end font-monospace">$171,990.00</td>
                    <td class="text-end font-monospace">$57,330.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">12 hrs/day, 7 days</td>
                    <td class="text-center fw-semibold">84</td>
                    <td class="text-center fw-semibold">3</td>
                    <td class="text-end font-monospace">$3,969.00</td>
                    <td class="text-end font-monospace">$17,185.77</td>
                    <td class="text-end font-monospace">$206,388.00</td>
                    <td class="text-end font-monospace">$68,796.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">112 hrs/week Coverage</td>
                    <td class="text-center fw-semibold">112</td>
                    <td class="text-center fw-semibold">4</td>
                    <td class="text-end font-monospace">$5,292.00</td>
                    <td class="text-end font-monospace">$22,914.36</td>
                    <td class="text-end font-monospace">$275,184.00</td>
                    <td class="text-end font-monospace">$68,796.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">24 hrs/day, Mon - Fri</td>
                    <td class="text-center fw-semibold">120</td>
                    <td class="text-center fw-semibold">5</td>
                    <td class="text-end font-monospace">$5,670.00</td>
                    <td class="text-end font-monospace">$24,551.10</td>
                    <td class="text-end font-monospace">$294,840.00</td>
                    <td class="text-end font-monospace">$58,968.00</td>
                </tr>
                <tr>
                    <td class="fw-semibold">
                        24/7 Coverage (168 hrs/week)
                        <div><span class="badge text-bg-primary mt-2">Most Popular</span></div>
                    </td>
                    <td class="text-center fw-semibold">168</td>
                    <td class="text-center fw-semibold">6</td>
                    <td class="text-end font-monospace">$7,938.00</td>
                    <td class="text-end font-monospace">$34,371.54</td>
                    <td class="text-end font-monospace">$412,776.00</td>
                    <td class="text-end font-monospace">$68,796.00</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
