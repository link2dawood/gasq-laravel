{{-- Part 2, Section D — Performance & Integrity + Final Review --}}
<h4 class="mb-3">Part 2 — Section D: Performance &amp; Integrity</h4>

@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q24_three_references', 'label' => '24. Can your company provide 3 client references?', 'required' => true])
@include('vendor-questionnaires._partials.yesno', ['name' => 'p2_q25_past_performance', 'label' => '25. Can your company provide proof of past performance?', 'required' => true])

<hr class="my-4">

<h5>Review Before Submitting</h5>
<p class="text-muted small mb-3">
    Submitting will lock this questionnaire and email a packet (PDF + uploaded documents) to the buyer.
    The buyer will receive a secure link valid for 14 days.
</p>

<ul class="list-group mb-3">
    <li class="list-group-item d-flex justify-content-between">
        <span>Required documents uploaded</span>
        <span class="badge bg-secondary">{{ $documents->count() }} / {{ count($documentTypes) }}</span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Step status</span>
        <span class="text-muted small">Click <strong>Save &amp; Exit</strong> to resume later, or use <strong>Send Response to Buyer</strong> below.</span>
    </li>
</ul>
