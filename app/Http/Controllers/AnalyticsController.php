<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Bid;
use App\Models\DiscoveryCall;
use App\Models\JobPosting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        $now = Carbon::now();
        $from = $now->copy()->subDays(7);

        $metrics = [
            'total_users' => User::count(),
            'total_buyers' => User::where('user_type', 'buyer')->count(),
            'total_vendors' => User::where('user_type', 'vendor')->count(),
            'total_jobs' => JobPosting::count(),
            'total_bids' => Bid::count(),
            'total_discovery_calls' => DiscoveryCall::count(),
        ];

        $eventsByType = AnalyticsEvent::selectRaw('event_type, count(*) as count')
            ->where('created_at', '>=', $from)
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $dailyEvents = AnalyticsEvent::selectRaw('DATE(created_at) as day, count(*) as count')
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.analytics', [
            'metrics' => $metrics,
            'eventsByType' => $eventsByType,
            'dailyEvents' => $dailyEvents,
            'from' => $from,
            'to' => $now,
        ]);
    }
}

