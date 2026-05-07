{{-- Part 1, Section B — Pricing Responsiveness --}}
<h4 class="mb-3">Part 1 — Section B: Pricing Responsiveness</h4>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q18_pricing_accepted', 'label' => "18. Are you accepting the buyer's proposed pricing range?", 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q19_schedule_accepted', 'label' => '19. Are you accepting the proposed coverage schedule?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p1_q20_pricing_sustainable', 'label' => '20. Can you sustain operations at the proposed pricing structure?', 'required' => true])
