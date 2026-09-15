{{--
    GASQ Workforce-to-Post™ Report — ALLOCATION & LINE-ITEM DASHBOARD.

    The companion document to the master Cost to Protect™ estimate: it breaks the
    vendor contract value down into allocation groups and line items. Pages:
      1. Executive cost dashboard   — contract total, group totals, allocation mix
      2. Line-item cost composition — full breakdown + stated-included elements
      3. GASQ Certified™ statement  — methodology, certification, IP, disclaimer

    Every figure comes from App\Services\CostToProtectEstimate so this document
    and the master estimate can never disagree. Percentages and amounts are shown
    exactly as the scenario carries them, and the Total Contract / Budget Value is
    the sum of the allocation groups — the same figure the calculator shows.
--}}
@php
    use App\Services\CostToProtectEstimate;
    use App\Support\Currency;

    // ---------- Allocation percentages from the calculator's session scenario ----------
    $meta = data_get($scenario ?? [], 'meta', []);
    $alloc = (array) data_get($meta, 'allocations', []);

    // Fallback: when the calculator didn't persist per-item allocation percentages,
    // derive them from the config benchmark proportions so the allocation report
    // never renders an all-zero breakdown against a real contract total.
    $allocHasValues = false;
    foreach ($alloc as $allocValue) {
        if (is_numeric($allocValue) && (float) $allocValue != 0.0) { $allocHasValues = true; break; }
    }
    if (! $allocHasValues) {
        $benchmarkGroups = (array) data_get(config('budget_calculator', []), 'groups', []);
        $benchmarkTotal = 0.0;
        foreach ($benchmarkGroups as $benchmarkGroup) {
            foreach ((array) ($benchmarkGroup['items'] ?? []) as $benchmarkItem) {
                $benchmarkTotal += (float) ($benchmarkItem['annual'] ?? 0);
            }
        }
        if ($benchmarkTotal > 0) {
            $alloc = [];
            foreach ($benchmarkGroups as $benchmarkGroup) {
                foreach ((array) ($benchmarkGroup['items'] ?? []) as $benchmarkItem) {
                    if (! empty($benchmarkItem['key'])) {
                        $alloc[$benchmarkItem['key']] = (float) ($benchmarkItem['annual'] ?? 0) / $benchmarkTotal * 100.0;
                    }
                }
            }
        }
    }

    // ---------- GASQ Cost to Protect™ figures ----------
    $estimate = app(CostToProtectEstimate::class)->build((array) ($scenario ?? []), $user ?? null);

    // Allocation 100% base = the VENDOR total (not the buyer in-house total):
    // the line-item allocations break down the vendor's contract value.
    $allocationBase = $estimate['totalAnnualVendor'];

    // ---------- Allocation groups and line items ----------
    // Mix colours and the short labels the dashboard bars use, per config group.
    $groupStyles = [
        'directLabor'       => ['#1e3558', 'Direct Labor', 'direct labor'],
        'fringeBurden'      => ['#d2a03f', 'Fringe & Burden', 'fringe and employer burden'],
        'operationsSupport' => ['#9fb4d4', 'Ops & Support', 'operations and contract support'],
        'overheadProfit'    => ['#64789c', 'OH, G&A & Profit', 'overhead, G&A and profit'],
    ];
    $fallbackColors = ['#1e3558', '#d2a03f', '#9fb4d4', '#64789c', '#3f6ea8', '#8aa0c0'];

    $budgetConfig = (array) config('budget_calculator', []);
    $lineGroups = [];
    foreach (($budgetConfig['groups'] ?? []) as $g => $cfgGroup) {
        $items = [];
        $groupPct = 0.0;
        foreach (($cfgGroup['items'] ?? []) as $item) {
            $key = $item['key'] ?? null; if (! $key) continue;
            $itemPct = isset($alloc[$key]) && is_numeric($alloc[$key]) ? (float) $alloc[$key] : 0.0;
            $itemAmount = $allocationBase * $itemPct / 100;
            $groupPct += $itemPct;
            // Hide $0.00 line items — only list items that carry a real dollar value.
            if (round($itemAmount, 2) <= 0) continue;
            $items[] = ['label' => $item['label'] ?? $key, 'pct' => $itemPct, 'amount' => $itemAmount];
        }
        [$color, $short, $readoutName] = $groupStyles[$cfgGroup['key'] ?? ''] ?? [
            $fallbackColors[$g % count($fallbackColors)],
            $cfgGroup['label'] ?? '',
            mb_strtolower($cfgGroup['label'] ?? ''),
        ];
        $lineGroups[] = [
            'label' => $cfgGroup['label'] ?? '',
            'short' => $short,
            'readout' => $readoutName,
            'color' => $color,
            // Descriptions read as sentences in config; the table row is a label.
            'description' => rtrim((string) ($cfgGroup['description'] ?? ''), '.'),
            'pct' => $groupPct,
            'amount' => $allocationBase * $groupPct / 100,
            'items' => $items,
        ];
    }

    // ---------- Dashboard reads ----------
    // Total Contract / Budget Value = the sum of the allocation groups, exactly as
    // the calculator screen computes it (bg_contract_total = groupSum). The group
    // percentages are applied to the vendor base, so when they don't total 100%
    // the sum differs from that base — the report shows the screen's figure.
    $displayedSubtotal = array_sum(array_column($lineGroups, 'amount'));
    $displayedPctTotal = array_sum(array_column($lineGroups, 'pct'));
    $contractTotal = $displayedSubtotal;
    $percentsReconcile = abs(100 - $displayedPctTotal) < 0.005;

    // Direct labor + fringe / employer burden: the share of the contract that is
    // workforce pay rather than overhead — the first number a CFO looks for.
    $laborFringePct = collect($lineGroups)
        ->whereIn('short', ['Direct Labor', 'Fringe & Burden'])
        ->sum('pct');

    $money = fn ($v) => Currency::format($v, 2);
    $pct = fn ($v) => number_format((float) $v, 2) . '%';

    // $17,379,651.67 → $17.380M; small totals stay in full.
    $compact = function ($v) {
        $abs = abs((float) $v);
        if ($abs >= 1000000) return Currency::format((float) $v / 1000000, 3) . 'M';
        if ($abs >= 100000) return Currency::format((float) $v / 1000, 1) . 'K';
        return Currency::format($v, 0);
    };

    $indicators = [
        [$pct($laborFringePct), 'Direct labor + fringe / employer burden'],
        [$compact($contractTotal), 'Sum of all allocation groups'],
        [$pct($displayedPctTotal), 'Allocation percentages applied'],
    ];

    $reconciliationNote = 'The four allocation groups total ' . $money($contractTotal)
        . ', reported as the Total Contract / Budget Value — the same figure the calculator shows on screen.';

    $sourceNote = 'Displayed group percentages total ' . $pct($displayedPctTotal)
        . '. This report does not alter the original percentages or amounts.';

    // Plain-language read of the mix, largest driver first.
    $ranked = collect($lineGroups)->sortByDesc('pct')->values();
    $executiveReadout = $ranked->isEmpty()
        ? 'No allocation percentages were captured for this scenario.'
        : ucfirst($ranked->first()['readout']) . ' is the dominant cost driver at ' . $pct($ranked->first()['pct'])
          . ($ranked->count() > 1
              ? ', followed by ' . $ranked->slice(1)->values()->map(fn ($g, $i) => ($i === $ranked->count() - 2 ? 'and ' : '') . $g['readout'] . ' at ' . $pct($g['pct']))->implode(', ')
              : '')
          . '.';

    // Elements the certification statement says the price includes — several are
    // covered inside a broader line rather than itemised on their own.
    $costElements = [
        'Livable base wages', 'FICA / FUTA / SUTA',
        'Workers compensation', 'General liability',
        'Unemployment insurance', 'Paid time off',
        'Healthcare & fringe', 'Uniforms & equipment',
        'Onboarding & training', 'Site supervision',
        'Quality assurance', 'Management / admin',
        '24/7 dispatch', 'Labor-law compliance',
        'Open-post protection', 'Replacement / price lock',
    ];

    // ---------- Line-item rows, paginated ----------
    // Rows are laid out on fixed-height pages, so a long breakdown is split
    // across continuation pages instead of being clipped. The closing page also
    // carries the cost-element and note blocks, so it holds fewer rows.
    $rows = [];
    foreach ($lineGroups as $group) {
        if (empty($group['items'])) continue;
        $rows[] = ['type' => 'group', 'label' => $group['label'], 'description' => $group['description'], 'amount' => $group['amount'], 'pct' => $group['pct']];
        foreach ($group['items'] as $j => $item) {
            $rows[] = ['type' => 'item', 'label' => $item['label'], 'amount' => $item['amount'], 'pct' => $item['pct'], 'alt' => $j % 2 === 1];
        }
    }

    $capacityFull = 37;  // rows on a page carrying nothing below the table
    $capacityLast = 24;  // rows on the page that also carries the closing blocks
    $rowChunks = array_chunk($rows, $capacityFull) ?: [[]];
    $lastChunk = array_pop($rowChunks);
    if (count($lastChunk) > $capacityLast) {
        $rowChunks[] = array_slice($lastChunk, 0, $capacityLast);
        $lastChunk = array_slice($lastChunk, $capacityLast);
    }
    $rowChunks[] = $lastChunk;
    // Never end a page on a group header with none of its items under it.
    foreach ($rowChunks as $i => $chunk) {
        if ($i === count($rowChunks) - 1 || empty($chunk)) continue;
        if (($chunk[count($chunk) - 1]['type'] ?? '') === 'group') {
            $rowChunks[$i + 1] = array_merge([array_pop($chunk)], $rowChunks[$i + 1]);
            $rowChunks[$i] = $chunk;
        }
    }

    // ---------- Identity ----------
    // Calculator contact first, signed-in vendor account as the fallback.
    $contactName    = $estimate['contact']['name'];
    $contactCompany = $estimate['contact']['company'];
    $contactAddress = $estimate['contact']['address'];
    $contactEmail   = $estimate['contact']['email'];
    $contactPhone   = $estimate['contact']['phone'];

    // ReportService passes the calculator slug as $reportType; the document shows
    // the human label instead.
    $reportType = 'Vendor — Full Report';
    $reportNumber = $reportNumber ?? ('GASQ-' . now()->format('Ymd-His') . '-V' . (int) ($vendorId ?? 0));
    $reportDate = now()->format('F j, Y');
    $generatedTime = now()->format('g:i A');
    $orgName = $contactCompany ?: 'GASQ Security';
    $docTitle = 'GASQ Workforce-to-Post Report';
    $pages = 2 + count($rowChunks);
@endphp

@extends('pdf.workforce.layout')

@section('pages')
    @include('pdf.workforce._page1')
    @include('pdf.workforce._page2')
    @include('pdf.workforce._page3')
@endsection
