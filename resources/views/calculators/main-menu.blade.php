@extends('layouts.app')

@section('title', 'Main Menu Calculator')

@section('content')
<div class="container py-4">
    <h1 class="h2 mb-4">Main Menu Calculator</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link {{ $activeTab === 'security' ? 'active' : '' }}" href="?tab=security">Security Cost</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeTab === 'manpower' ? 'active' : '' }}" href="?tab=manpower">Manpower</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeTab === 'economic' ? 'active' : '' }}" href="?tab=economic">Economic</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeTab === 'billrate' ? 'active' : '' }}" href="?tab=billrate">Bill Rate</a></li>
    </ul>

    <form method="POST" action="{{ route('main-menu-calculator.index') }}">
        @csrf
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        @if($activeTab === 'security')
            <x-card title="Security cost">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Location</label>
                        <select name="location" class="form-select">
                            <option value="california">California</option>
                            <option value="new-york">New York</option>
                            <option value="texas">Texas</option>
                            <option value="florida">Florida</option>
                            <option value="illinois">Illinois</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Hours per week</label>
                        <input type="number" name="hours_per_week" class="form-control" step="0.5" min="0" value="40">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Guards</label>
                        <input type="number" name="guards" class="form-control" min="1" value="1">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
            </x-card>
        @endif

        @if($activeTab === 'manpower')
            <x-card title="Manpower hours">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Site coverage (hours)</label>
                        <input type="number" name="site_coverage" class="form-control" step="0.5" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Shift pattern</label>
                        <select name="shift_pattern" class="form-select">
                            <option value="8-hour">8-hour</option>
                            <option value="10-hour">10-hour</option>
                            <option value="12-hour">12-hour</option>
                            <option value="24-hour">24-hour</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Scheduling factor</label>
                        <input type="number" name="scheduling_factor" class="form-control" step="0.1" value="1.4">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
            </x-card>
        @endif

        @if($activeTab === 'economic')
            <x-card title="Economic justification">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Employee hourly cost ($)</label>
                        <input type="number" name="employee_hourly_cost" class="form-control" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vendor hourly cost ($)</label>
                        <input type="number" name="vendor_hourly_cost" class="form-control" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Weekly hours</label>
                        <input type="number" name="weekly_hours" class="form-control" step="0.5">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Weeks in year</label>
                        <input type="number" name="weeks_in_year" class="form-control" value="52">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
            </x-card>
        @endif

        @if($activeTab === 'billrate')
            <x-card title="Bill rate">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Base pay ($)</label>
                        <input type="number" name="base_pay" class="form-control" step="0.01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Overhead (%)</label>
                        <input type="number" name="overhead" class="form-control" step="0.1" value="35">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Profit margin (%)</label>
                        <input type="number" name="profit_margin" class="form-control" step="0.1" value="15">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Calculate</button>
            </x-card>
        @endif
    </form>

    @if(!empty($result))
        <x-card title="Result" class="mt-4">
            @foreach($result as $key => $val)
                <p class="mb-1"><strong>{{ str_replace('_', ' ', ucfirst($key)) }}:</strong>
                @if(is_numeric($val) && str_contains((string)$val, '.'))
                    {{ number_format((float)$val, 2) }}
                @else
                    {{ $val }}
                @endif
                </p>
            @endforeach
            <x-report-actions report-type="main-menu" />
        </x-card>
    @endif
</div>
@endsection
