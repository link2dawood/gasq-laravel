@extends('layouts.app')
@section('title', 'How to Use the Mobile Patrol Calculator')
@section('header_variant', 'dashboard')

@push('styles')
<style>
  .howto-page { background: linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); }
  .howto-hero {
    background: linear-gradient(135deg,#062d79 0%,#0a47b8 100%);
    color: #fff; border-radius: 1rem; padding: 2rem;
  }
  .howto-card {
    background:#fff; border:1px solid rgba(15,23,42,.08);
    border-radius: 1rem; padding: 1.5rem 1.75rem; margin-bottom: 1.25rem;
  }
  .howto-step-num {
    display:inline-flex; align-items:center; justify-content:center;
    width:2rem; height:2rem; border-radius:50%;
    background:#062d79; color:#fff; font-weight:700; margin-right:.6rem;
  }
  .howto-tip {
    background:#fef9c3; border-left:4px solid #ca8a04;
    border-radius:.5rem; padding:.85rem 1rem; margin-top:.75rem;
  }
  .howto-callout {
    background:#eff6ff; border-left:4px solid #2563eb;
    border-radius:.5rem; padding:.85rem 1rem; margin-top:.75rem;
  }
  .howto-card ul { margin-bottom: 0; }
  .howto-card h3 { color:#062d79; }
</style>
@endpush

@section('content')
<div class="howto-page py-4 py-md-5 px-3 px-md-4">
  <div class="container-xl" style="max-width: 60rem;">

    <div class="d-flex align-items-start gap-3 mb-4">
      <a href="{{ route('mobile-patrol-calculator') }}" class="mp24-back" style="width:2.75rem;height:2.75rem;border-radius:.9rem;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(15,23,42,.12);background:rgba(255,255,255,.8);color:#062d79;text-decoration:none;">
        <i class="fa fa-arrow-left"></i>
      </a>
      <div>
        <div class="text-uppercase small fw-semibold text-gasq-muted" style="letter-spacing:.08em">Help &amp; Guide</div>
        <h1 class="h2 fw-bold mb-0">How to Use the Mobile Patrol Calculator</h1>
      </div>
    </div>

    <div class="howto-hero mb-4">
      <p class="mb-0" style="max-width:56rem;">
        The GASQ Mobile Patrol Calculator is a budgeting and pricing tool designed to help buyers and
        security providers estimate the true operational cost of mobile patrol security services.
      </p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">This Calculator Helps Determine</h3>
      <ul>
        <li>Estimated patrol pricing</li>
        <li>Cost per patrol hit/check</li>
        <li>Monthly and annual patrol costs</li>
        <li>Workforce and vehicle operating costs</li>
        <li>Realistic service budgets based on patrol frequency</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">1</span>Enter Patrol Frequency</h3>
      <p class="mb-2">Select how often patrol checks or property visits will occur.</p>
      <strong>Examples:</strong>
      <ul>
        <li>1 patrol every hour</li>
        <li>2 patrols per shift</li>
        <li>4 patrols nightly</li>
        <li>21 patrol checks per week</li>
        <li>84 patrol checks per week</li>
      </ul>
      <p class="mt-2 mb-0">The patrol frequency directly affects labor hours, fuel usage, vehicle wear and tear, and staffing requirements.</p>
      <div class="howto-tip"><strong>Tip:</strong> Higher patrol frequency increases operational costs but also improves visibility, deterrence, and response coverage.</div>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">2</span>Enter Number of Properties or Stops</h3>
      <p class="mb-2">Input the number of locations, properties, or patrol stops that will be serviced.</p>
      <strong>Examples:</strong>
      <ul>
        <li>Single apartment complex</li>
        <li>Multiple HOA communities</li>
        <li>Commercial shopping centers</li>
        <li>Construction sites</li>
        <li>Vehicle patrol routes</li>
      </ul>
      <p class="mt-2 mb-0">The calculator uses this information to estimate route workload and staffing capacity.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">3</span>Enter Patrol Coverage Hours</h3>
      <p class="mb-2">Input the number of hours patrol services are required.</p>
      <strong>Examples:</strong>
      <ul>
        <li>Night patrol only</li>
        <li>Weekend patrols</li>
        <li>24/7 vehicle patrol coverage</li>
        <li>After-hours lockup checks</li>
      </ul>
      <strong class="d-block mt-3">Typical Inputs:</strong>
      <ul>
        <li>Hours per Day</li>
        <li>Days per Week</li>
        <li>Weeks per Year</li>
      </ul>
      <p class="mt-2 mb-0">This determines the total annual patrol workload.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">4</span>Enter Officer Pay Rate</h3>
      <p class="mb-2">Input the baseline hourly wage paid to the patrol officer.</p>
      <strong>Examples:</strong>
      <ul>
        <li>$18.00/hour</li>
        <li>$22.00/hour</li>
        <li>$30.00/hour armed patrol</li>
      </ul>
      <p class="mt-2 mb-0">This is the starting labor cost before taxes, insurance, training, vehicle expenses, and workforce maintenance costs are applied.</p>
      <div class="howto-callout"><strong>Important:</strong> The calculator is designed to help estimate fully burdened operational pricing — not just payroll wages.</div>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">5</span>Enter Vehicle &amp; Equipment Costs</h3>
      <p class="mb-2">Input estimated operating expenses related to patrol operations.</p>
      <strong>Examples:</strong>
      <ul>
        <li>Fuel</li>
        <li>Vehicle maintenance</li>
        <li>Insurance</li>
        <li>Vehicle depreciation</li>
        <li>Uniforms</li>
        <li>Patrol technology</li>
        <li>Communication equipment</li>
      </ul>
      <p class="mt-2 mb-0">These expenses are commonly overlooked in traditional pricing models but are critical for determining true patrol operating costs.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">6</span>Review Workforce Maintenance Costs</h3>
      <p class="mb-2">The calculator factors in non-productive but necessary workforce costs such as:</p>
      <ul>
        <li>PTO</li>
        <li>Sick leave</li>
        <li>Training time</li>
        <li>Turnover coverage</li>
        <li>Scheduling gaps</li>
        <li>Administrative overhead</li>
      </ul>
      <p class="mt-2 mb-0">These "hours paid but not worked" are essential when determining realistic and sustainable patrol pricing.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">7</span>Review the Results</h3>
      <p class="mb-2">The calculator will automatically estimate:</p>
      <ul>
        <li>Hourly patrol rate</li>
        <li>Cost per patrol hit/check</li>
        <li>Monthly service cost</li>
        <li>Annual operating cost</li>
        <li>Estimated fully burdened patrol pricing</li>
      </ul>
      <p class="mt-3 mb-1">This helps buyers understand: <em>"What should this service realistically cost?"</em></p>
      <p class="mb-0">It also helps vendors determine: <em>"What price is sustainable and operationally responsible?"</em></p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">How Buyers Should Use This Tool</h3>
      <ul>
        <li>Build realistic security budgets</li>
        <li>Compare in-house vs outsourced patrol costs</li>
        <li>Validate vendor pricing</li>
        <li>Understand the total cost of ownership (TCO)</li>
        <li>Avoid unrealistically low bids</li>
      </ul>
      <p class="mt-2 mb-0">The goal is to help buyers make informed financial decisions before purchasing security services.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">How Vendors Should Use This Tool</h3>
      <ul>
        <li>Validate patrol pricing</li>
        <li>Reduce underbidding risk</li>
        <li>Estimate workforce sustainment costs</li>
        <li>Improve profitability analysis</li>
        <li>Create price realism documentation</li>
        <li>Justify patrol bill rates to customers</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">Recommended Best Practice</h3>
      <p class="mb-2">Use the calculator before:</p>
      <ul>
        <li>Requesting security proposals</li>
        <li>Posting RFPs</li>
        <li>Negotiating patrol contracts</li>
        <li>Expanding patrol routes</li>
        <li>Adjusting service schedules</li>
      </ul>
      <p class="mt-2 mb-0">Knowing your estimated operational cost before purchasing security services helps reduce pricing confusion and improves decision-making.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">Important Notice</h3>
      <p class="mb-2">This calculator provides budgetary and planning estimates only. Final pricing may vary based on:</p>
      <ul>
        <li>Geographic location</li>
        <li>Crime risk level</li>
        <li>Armed vs unarmed services</li>
        <li>Vehicle type</li>
        <li>Patrol response requirements</li>
        <li>Insurance requirements</li>
        <li>Overtime and holiday coverage</li>
        <li>Local wage mandates</li>
        <li>Contract scope and compliance requirements</li>
      </ul>
    </div>

    <div class="text-center text-gasq-muted small mt-4 mb-2">
      <strong>GASQ — "Know Before You Buy" Pricing Tools</strong><br>
      CFO Tested. CFO Approved.<br>
      The Kelly Blue Book for Security Services Pricing.
    </div>

    <div class="text-center mb-5">
      <a href="{{ route('mobile-patrol-calculator') }}" class="btn btn-primary btn-lg px-5">
        <i class="fa fa-calculator me-2"></i> Open the Calculator
      </a>
    </div>

  </div>
</div>
@endsection
