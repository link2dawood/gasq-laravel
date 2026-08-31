@extends('layouts.app')

@section('title', 'Bill-Rate Breakdown')
@section('header_variant', 'dashboard')

@section('content')
@php
    // Built here rather than inline in @json below: the directive cannot parse a
    // multi-line array literal inside an arrow function.
    $existingLines = ($breakdown?->lineItems ?? collect())->map(fn ($l) => [
        'category' => $l->category,
        'label' => $l->label,
        'amount' => (float) $l->amount,
        'resource_classification' => $l->resource_classification,
        'allocation_method' => $l->allocation_method,
        'shared_across_accounts' => $l->shared_across_accounts,
    ])->values();
@endphp
<div class="container py-4 py-lg-5" style="max-width: 68rem;">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2">Line-Item Bill-Rate Breakdown</h1>
            <p class="text-gasq-muted mb-0" style="max-width: 46rem;">
                Declare every component of your proposed rate. Any line marked
                <strong>Shared resource</strong> must state how it was allocated and across how many
                accounts &mdash; that is what separates a validated allocation from an unsupported
                discount.
            </p>
        </div>
        <a href="{{ route('shared-resource.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('shared-resource.breakdown.store') }}" method="POST" id="breakdownForm">
        @csrf

        <div class="card gasq-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label small fw-semibold">Proposed bill rate ($/hour)</label>
                        <input type="number" step="0.01" min="0.01" name="proposed_bill_rate" id="proposedRate"
                               class="form-control form-control-lg"
                               value="{{ old('proposed_bill_rate', $breakdown?->proposed_bill_rate) }}" required>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Line items total</div>
                        <div class="h4 fw-bold mb-0" id="linesTotal">$0.00</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-uppercase text-gasq-muted fw-semibold">Reconciliation</div>
                        <div class="h5 fw-bold mb-0" id="reconcileState">
                            <span class="text-gasq-muted">&mdash;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card gasq-card mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle" id="linesTable">
                        <thead>
                            <tr>
                                <th style="min-width:150px;">Category</th>
                                <th style="min-width:170px;">Description</th>
                                <th style="min-width:110px;">$/hour</th>
                                <th style="min-width:170px;">Classification</th>
                                <th style="min-width:220px;">Allocation (shared only)</th>
                                <th style="min-width:110px;">Accounts</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody"></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addLineBtn">
                    <i class="fa fa-plus me-1"></i>Add line
                </button>
            </div>
        </div>

        {{-- Vendor certification (spec §41, RULE 14) --}}
        <div class="card gasq-card mb-4">
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="certified" id="certified" value="1" required>
                    <label class="form-check-label small" for="certified">
                        I certify that this bill-rate breakdown accurately reflects the components of
                        the proposed rate and identifies all known material recurring charges and
                        pricing assumptions associated with this opportunity.
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Submit breakdown for GASQ review</button>
    </form>
</div>

@push('scripts')
<script>
// Live reconciliation. The server recomputes this from the stored line items on submit —
// this is only so the vendor is not surprised at the end (spec §31: prevent the error,
// don't just report it).
(function () {
    var CATEGORIES = @json($categories);
    var CLASSIFICATIONS = @json($classifications);
    var EXISTING = @json($existingLines);

    var body = document.getElementById('linesBody');
    var idx = 0;

    function options(map, selected) {
        return Object.keys(map).map(function (k) {
            return '<option value="' + k + '"' + (k === selected ? ' selected' : '') + '>' + map[k] + '</option>';
        }).join('');
    }

    function addRow(data) {
        data = data || {};
        var i = idx++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="lines[' + i + '][category]" class="form-select form-select-sm">' + options(CATEGORIES, data.category) + '</select></td>' +
            '<td><input name="lines[' + i + '][label]" class="form-control form-control-sm" value="' + (data.label || '') + '" required></td>' +
            '<td><input name="lines[' + i + '][amount]" type="number" step="0.0001" min="0" class="form-control form-control-sm js-amount" value="' + (data.amount != null ? data.amount : '') + '" required></td>' +
            '<td><select name="lines[' + i + '][resource_classification]" class="form-select form-select-sm js-class">' + options(CLASSIFICATIONS, data.resource_classification) + '</select></td>' +
            '<td><input name="lines[' + i + '][allocation_method]" class="form-control form-control-sm js-alloc" placeholder="e.g. $3.50/hr spread across 6 accounts" value="' + (data.allocation_method || '') + '"></td>' +
            '<td><input name="lines[' + i + '][shared_across_accounts]" type="number" min="0" class="form-control form-control-sm js-accounts" value="' + (data.shared_across_accounts || '') + '"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger js-remove" aria-label="Remove line">&times;</button></td>';
        body.appendChild(tr);
        syncRow(tr);
        recalc();
    }

    // Allocation fields only matter for shared lines; requiring them elsewhere is noise.
    function syncRow(tr) {
        var isShared = tr.querySelector('.js-class').value === 'shared';
        ['.js-alloc', '.js-accounts'].forEach(function (sel) {
            var el = tr.querySelector(sel);
            el.disabled = !isShared;
            el.required = isShared;
            if (!isShared) el.value = '';
        });
    }

    function recalc() {
        var total = 0;
        body.querySelectorAll('.js-amount').forEach(function (el) {
            var v = parseFloat(el.value);
            if (!isNaN(v)) total += v;
        });

        document.getElementById('linesTotal').textContent = '$' + total.toFixed(2);

        var proposed = parseFloat(document.getElementById('proposedRate').value);
        var state = document.getElementById('reconcileState');

        if (isNaN(proposed) || body.children.length === 0) {
            state.innerHTML = '<span class="text-gasq-muted">&mdash;</span>';
            return;
        }

        if (Math.abs(total - proposed) < 0.005) {
            state.innerHTML = '<span class="text-success"><i class="fa fa-circle-check me-1"></i>Balanced</span>';
        } else {
            var diff = (total - proposed).toFixed(2);
            state.innerHTML = '<span class="text-danger"><i class="fa fa-circle-xmark me-1"></i>Out by $' + Math.abs(diff) + '</span>';
        }
    }

    body.addEventListener('input', recalc);
    body.addEventListener('change', function (e) {
        if (e.target.classList.contains('js-class')) syncRow(e.target.closest('tr'));
        recalc();
    });
    body.addEventListener('click', function (e) {
        if (e.target.classList.contains('js-remove')) {
            e.target.closest('tr').remove();
            recalc();
        }
    });
    document.getElementById('proposedRate').addEventListener('input', recalc);
    document.getElementById('addLineBtn').addEventListener('click', function () { addRow(); });

    if (EXISTING.length) {
        EXISTING.forEach(addRow);
    } else {
        addRow({ category: 'direct_labor', label: 'Officer wage', resource_classification: 'direct_labor' });
    }
})();
</script>
@endpush
@endsection
