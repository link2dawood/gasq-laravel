@extends('layouts.app')
@section('title', 'How to Use the Mobile Patrol Hit Calculator')
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
  .howto-freq-badge {
    display:inline-block; background:#062d79; color:#fff;
    padding:.25rem .75rem; border-radius:999px;
    font-size:.85rem; font-weight:700; margin-bottom:.5rem;
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
  .howto-q {
    background: rgba(6,45,121,.06); border-radius:.75rem;
    padding: 1rem 1.25rem; font-style: italic; font-weight: 600;
    color:#062d79; text-align:center;
  }
</style>
@endpush

@section('content')
<div class="howto-page py-4 py-md-5 px-3 px-md-4">
  <div class="container-xl" style="max-width: 60rem;">

    <div class="d-flex align-items-start gap-3 mb-4">
      <a href="{{ route('mobile-patrol-hit-calculator.index') }}" class="mp24-back" style="width:2.75rem;height:2.75rem;border-radius:.9rem;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(15,23,42,.12);background:rgba(255,255,255,.8);color:#062d79;text-decoration:none;">
        <i class="fa fa-arrow-left"></i>
      </a>
      <div>
        <div class="text-uppercase small fw-semibold text-gasq-muted" style="letter-spacing:.08em">Help &amp; Guide</div>
        <h1 class="h2 fw-bold mb-0">How to Use the GASQ Mobile Patrol Hit Calculator</h1>
      </div>
    </div>

    <div class="howto-hero mb-4">
      <p class="mb-3" style="max-width:56rem;">
        The GASQ Mobile Patrol Hit Calculator is a budgeting and pricing tool designed to help buyers and
        security providers estimate realistic mobile patrol hit/check pricing based on budget spend and patrol frequency.
      </p>
      <p class="mb-0 fw-semibold" style="max-width:56rem;">
        It helps answer one important question: <em>"How many patrol checks can my budget realistically support?"</em>
      </p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">The Calculator Estimates</h3>
      <ul>
        <li>Weekly patrol hit coverage</li>
        <li>Estimated monthly coverage</li>
        <li>Budget sustainability</li>
        <li>Patrol frequency planning</li>
        <li>Cost-per-hit budgeting</li>
        <li>Mobile patrol service expectations</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">What Is a "Patrol Hit" or "Patrol Check"?</h3>
      <p>A patrol hit/check is a scheduled or random security visit to a property, location, or asset.</p>
      <strong>Examples include:</strong>
      <ul>
        <li>Drive-through patrols</li>
        <li>Parking lot inspections</li>
        <li>Lock/unlock checks</li>
        <li>Alarm response checks</li>
        <li>Vacant property inspections</li>
        <li>HOA patrol visits</li>
        <li>Construction site inspections</li>
        <li>Business perimeter checks</li>
      </ul>
      <strong class="d-block mt-3">Each patrol hit includes:</strong>
      <ul>
        <li>Travel time</li>
        <li>Officer labor</li>
        <li>Vehicle expenses</li>
        <li>Reporting time</li>
        <li>Patrol verification</li>
        <li>Administrative support</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">1</span>Enter Your Planned Budget Amount</h3>
      <p class="mb-2">Input the amount you are willing to spend for mobile patrol services.</p>
      <strong>Examples:</strong>
      <ul>
        <li>$500</li>
        <li>$2,500</li>
        <li>$10,000</li>
        <li>Annual or monthly patrol budgets</li>
      </ul>
      <p class="mt-2 mb-0">This amount becomes the baseline used to estimate how much patrol coverage your budget can realistically support.</p>
      <div class="howto-tip"><strong>Tip:</strong> The calculator is designed to help buyers determine affordability before requesting proposals from vendors.</div>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">2</span>Enter Number of Weekly Patrol Checks</h3>
      <p class="mb-2">Input the number of patrol hits/checks you want completed each week.</p>
      <strong>Examples:</strong>
      <ul>
        <li>21 weekly checks</li>
        <li>28 weekly checks</li>
        <li>42 weekly checks</li>
        <li>56 weekly checks</li>
        <li>84 weekly checks</li>
      </ul>
      <p class="mt-2 mb-0">The higher the patrol frequency, the faster the budget is consumed.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">Understanding Weekly Patrol Check Frequencies</h3>

      <div class="mb-3">
        <span class="howto-freq-badge">21 Weekly Checks</span>
        <ul>
          <li>Approximately 1 patrol every 8 hours</li>
          <li>3 patrols per day</li>
          <li><strong>Best for:</strong> low-to-moderate risk properties, budget-conscious coverage</li>
        </ul>
      </div>

      <div class="mb-3">
        <span class="howto-freq-badge">28 Weekly Checks</span>
        <ul>
          <li>Approximately 1 patrol every 6 hours</li>
          <li>4 patrols per day</li>
          <li><strong>Best for:</strong> moderate visibility requirements, commercial properties, small apartment communities</li>
        </ul>
      </div>

      <div class="mb-3">
        <span class="howto-freq-badge">42 Weekly Checks</span>
        <ul>
          <li>Approximately 1 patrol every 4 hours</li>
          <li>6 patrols per day</li>
          <li><strong>Best for:</strong> higher-risk locations, construction sites, retail centers, active deterrence programs</li>
        </ul>
      </div>

      <div class="mb-3">
        <span class="howto-freq-badge">56 Weekly Checks</span>
        <ul>
          <li>Approximately 1 patrol every 3 hours</li>
          <li>8 patrols per day</li>
          <li><strong>Best for:</strong> high-crime areas, large residential communities, frequent visibility requirements</li>
        </ul>
      </div>

      <div>
        <span class="howto-freq-badge">84 Weekly Checks</span>
        <ul>
          <li>Approximately 1 patrol every 2 hours</li>
          <li>12 patrols per day</li>
          <li><strong>Best for:</strong> critical properties, high-liability locations, frequent incident environments</li>
        </ul>
      </div>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-2"><span class="howto-step-num">3</span>Review the Results</h3>
      <p class="mb-2">The calculator automatically estimates:</p>
      <ul>
        <li>Number of weeks your budget will cover</li>
        <li>Number of months your budget will cover</li>
        <li>Estimated patrol sustainability</li>
        <li>Approximate patrol service duration</li>
      </ul>
      <p class="mt-3 mb-2">This allows buyers to quickly determine:</p>
      <ul>
        <li>Whether their budget is realistic</li>
        <li>Whether patrol frequency should be increased or reduced</li>
        <li>Whether static guards may be more appropriate</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">Example</h3>
      <p class="mb-2"><strong>Example Budget:</strong></p>
      <ul>
        <li>Budget: $5,000</li>
        <li>Patrol Frequency: 42 Weekly Checks</li>
      </ul>
      <p class="mt-3 mb-2">The calculator may show:</p>
      <ul>
        <li>Estimated number of weeks covered</li>
        <li>Estimated number of months covered</li>
        <li>Approximate patrol sustainability based on selected frequency</li>
      </ul>
      <div class="howto-q mt-3">"How long will my patrol budget realistically last?"</div>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">How Buyers Should Use This Calculator</h3>
      <ul>
        <li>Estimate realistic patrol budgets</li>
        <li>Compare patrol frequency options</li>
        <li>Avoid under-budgeting security services</li>
        <li>Determine affordability before requesting proposals</li>
        <li>Understand the relationship between patrol frequency and cost</li>
      </ul>
      <p class="mt-2 mb-0">The calculator removes much of the guesswork from mobile patrol budgeting.</p>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">How Security Vendors Should Use This Calculator</h3>
      <ul>
        <li>Validate patrol pricing</li>
        <li>Build customer budgets</li>
        <li>Reduce unrealistic customer expectations</li>
        <li>Demonstrate price realism</li>
        <li>Estimate patrol workload requirements</li>
        <li>Support proposal development</li>
      </ul>
      <p class="mt-3 mb-2">The calculator also helps explain why:</p>
      <ul>
        <li>More patrol hits require more labor</li>
        <li>Increased frequency increases vehicle costs</li>
        <li>Route density affects pricing</li>
        <li>Travel time impacts profitability</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">What Factors Affect Mobile Patrol Pricing?</h3>
      <ul>
        <li>Patrol frequency</li>
        <li>Property size</li>
        <li>Number of locations</li>
        <li>Travel distance</li>
        <li>Fuel costs</li>
        <li>Vehicle wear and tear</li>
        <li>Officer wages</li>
        <li>Insurance</li>
        <li>Reporting requirements</li>
        <li>Geographic crime risk</li>
        <li>Armed vs unarmed patrols</li>
        <li>Alarm response requirements</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">Important Notice</h3>
      <p class="mb-2">This calculator provides budgetary planning estimates only. Actual pricing may vary depending on:</p>
      <ul>
        <li>Geographic area</li>
        <li>Service scope</li>
        <li>Patrol duration</li>
        <li>Site conditions</li>
        <li>Emergency response requirements</li>
        <li>Insurance requirements</li>
        <li>Local wage mandates</li>
        <li>Contract requirements</li>
      </ul>
    </div>

    <div class="howto-card">
      <h3 class="h5 fw-bold mb-3">Recommended Best Practice</h3>
      <p class="mb-2">Before purchasing patrol services:</p>
      <ol>
        <li>Determine your available budget</li>
        <li>Decide desired patrol frequency</li>
        <li>Use the calculator to test affordability</li>
        <li>Compare coverage options</li>
        <li>Request vendor proposals based on realistic expectations</li>
      </ol>
      <p class="mt-2 mb-0">Knowing your budget before buying security services improves decision-making and reduces pricing confusion.</p>
    </div>

    <div class="text-center text-gasq-muted small mt-4 mb-2">
      <strong>GASQ — "Know Before You Buy" Pricing Tools</strong><br>
      CFO Tested. CFO Approved.<br>
      The Kelly Blue Book for Security Services Pricing.
    </div>

    <div class="text-center mb-5">
      <a href="{{ route('mobile-patrol-hit-calculator.index') }}" class="btn btn-primary btn-lg px-5">
        <i class="fa fa-calculator me-2"></i> Open the Calculator
      </a>
    </div>

  </div>
</div>
@endsection
