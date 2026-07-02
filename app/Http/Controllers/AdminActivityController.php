<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request): View
    {
        $query = AnalyticsEvent::query()
            ->with('user:id,name,email,user_type')
            ->latest();

        if ($request->filled('user')) {
            $term = $request->string('user');
            $query->whereHas('user', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->input('type') === 'writes') {
            $query->whereIn('event_data->method', ['POST', 'PUT', 'PATCH', 'DELETE']);
        } elseif ($request->input('type') === 'views') {
            $query->where('event_data->method', 'GET');
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $events = $query->paginate(50)->withQueryString();

        $eventTypes = AnalyticsEvent::query()
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('admin.activity', compact('events', 'eventTypes'));
    }
}
