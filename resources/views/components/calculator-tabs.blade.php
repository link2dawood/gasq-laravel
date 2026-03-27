@props(['active' => 'security'])

@php
    $tabs = [
        'security' => ['label' => 'Security Cost', 'href' => url('/main-menu-calculator?tab=security')],
        'manpower' => ['label' => 'Manpower Hours', 'href' => url('/main-menu-calculator?tab=manpower')],
        'economic' => ['label' => 'Economic ROI', 'href' => url('/main-menu-calculator?tab=economic')],
        'justification' => ['label' => 'Economic Justification', 'href' => url('/economic-justification')],
        'billrate' => ['label' => 'Bill Rate', 'href' => url('/main-menu-calculator?tab=billrate')],
    ];
@endphp

<ul class="nav nav-tabs mb-4" role="tablist">
    @foreach($tabs as $key => $tab)
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $active === $key ? 'active' : '' }}"
                href="{{ $tab['href'] }}"
            >
                {{ $tab['label'] }}
            </a>
        </li>
    @endforeach
</ul>

