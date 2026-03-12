<?php

namespace App\Http\Controllers;

use App\Models\DiscoveryCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoveryCallController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $existing = DiscoveryCall::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        return view('discovery-call.index', [
            'user' => $user,
            'existing' => $existing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preferred_time' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DiscoveryCall::create([
            'user_id' => $request->user()->id,
            'requested_at' => $data['preferred_time'],
            'status' => 'requested',
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('discovery-call.index')
            ->with('success', 'Discovery call requested. Our team will contact you to confirm the time.');
    }
}

