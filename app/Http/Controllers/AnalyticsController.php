<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Bid;
use App\Models\DiscoveryCall;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\Funnel;
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

        $funnelCounts = $this->funnelCounts($from);

        return view('admin.analytics', [
            'metrics' => $metrics,
            'eventsByType' => $eventsByType,
            'dailyEvents' => $dailyEvents,
            'from' => $from,
            'to' => $now,
            'journey' => $this->journeyWithConversion($funnelCounts),
            'sideEntries' => $this->sideEntries($funnelCounts),
            'registrationAbandoned' => max(
                0,
                ($funnelCounts[Funnel::REGISTRATION_STARTED] ?? 0) - ($funnelCounts[Funnel::REGISTRATION_COMPLETED] ?? 0)
            ),
            'registrationAbandonRate' => $this->rate(
                ($funnelCounts[Funnel::REGISTRATION_STARTED] ?? 0) - ($funnelCounts[Funnel::REGISTRATION_COMPLETED] ?? 0),
                $funnelCounts[Funnel::REGISTRATION_STARTED] ?? 0
            ),
        ]);
    }

    /**
     * Distinct sessions per funnel stage in the window.
     *
     * Counts sessions rather than raw events so that one person refreshing the
     * estimator five times is one journey, not five. Events with no session are
     * counted individually because they cannot be grouped.
     *
     * @return array<string, int>
     */
    private function funnelCounts(Carbon $from): array
    {
        $keys = array_merge(
            array_column(Funnel::BUYER_JOURNEY, 'key'),
            array_column(Funnel::SIDE_ENTRIES, 'key'),
        );

        return AnalyticsEvent::query()
            ->selectRaw('event_type, COUNT(DISTINCT COALESCE(session_id, CAST(id AS CHAR))) as sessions')
            ->whereIn('event_type', $keys)
            ->where('created_at', '>=', $from)
            ->groupBy('event_type')
            ->pluck('sessions', 'event_type')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * The ordered journey with step-to-step and top-of-funnel conversion.
     *
     * NOTE: these are stage volume ratios, not a cohort funnel — Laravel
     * regenerates the session id on login, so a single visitor cannot be followed
     * across the registration boundary. Read them as "how many reach this stage",
     * not "how many of these exact people continued".
     *
     * @param  array<string, int>  $counts
     * @return list<array<string, mixed>>
     */
    private function journeyWithConversion(array $counts): array
    {
        $rows = [];
        $previous = null;
        $top = $counts[Funnel::BUYER_JOURNEY[0]['key']] ?? 0;

        foreach (Funnel::BUYER_JOURNEY as $stage) {
            $value = $counts[$stage['key']] ?? 0;

            $rows[] = [
                'key' => $stage['key'],
                'label' => $stage['label'],
                'count' => $value,
                'from_previous' => $previous === null ? null : $this->rate($value, $previous),
                'from_top' => $this->rate($value, $top),
                'dropped' => $previous === null ? null : max(0, $previous - $value),
            ];

            $previous = $value;
        }

        return $rows;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array<string, mixed>>
     */
    private function sideEntries(array $counts): array
    {
        return array_map(
            fn (array $e) => $e + ['count' => $counts[$e['key']] ?? 0],
            Funnel::SIDE_ENTRIES,
        );
    }

    private function rate(int $numerator, int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }
}

