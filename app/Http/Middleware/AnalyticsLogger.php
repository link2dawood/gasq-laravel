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
            $method = $request->method();
            $isWrite = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

            // Log GET page views AND every write action (create/update/delete) so
            // admins have a full activity trail of what happens on the platform.
            if ($method === 'GET' || $isWrite) {
                $route = $request->route();
                $eventType = $route?->getName() ?: ($isWrite ? strtolower($method) . '_action' : 'page_view');

                $data = [
                    'method' => $method,
                    'path' => $request->path(),
                ];

                if ($method === 'GET') {
                    $data['query'] = $request->query();
                } else {
                    // URL route parameters only (e.g. the job id) — never the request
                    // body, so passwords / card details are never persisted.
                    $data['params'] = $route?->originalParameters() ?? [];
                    $data['status'] = $response->getStatusCode();
                }

                AnalyticsEvent::create([
                    'event_type' => $eventType,
                    'user_id' => $request->user()?->id,
                    'event_data' => $data,
                    'session_id' => $request->hasSession() ? $request->session()->getId() : null,
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

