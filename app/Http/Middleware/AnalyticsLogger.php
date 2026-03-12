<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsLogger
{
    /**
     * Handle an incoming request and log a simple analytics event.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            // Only log GET page views for now
            if ($request->method() === 'GET') {
                $route = $request->route();
                $eventType = $route?->getName() ?: 'page_view';

                AnalyticsEvent::create([
                    'event_type' => $eventType,
                    'user_id' => $request->user()?->id,
                    'event_data' => [
                        'path' => $request->path(),
                        'query' => $request->query(),
                    ],
                    'session_id' => $request->session()->getId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (\Throwable) {
            // Never break the request because of analytics
        }

        return $response;
    }
}

