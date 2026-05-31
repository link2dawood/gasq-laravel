<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NdaController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user && $user->nda_accepted_at) {
            return redirect()->intended(route('home'));
        }

        return view('auth.nda');
    }

    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'acknowledge' => ['required', Rule::in(['1', 'on', 'true'])],
        ], [
            'acknowledge.required' => 'You must check the acknowledgment box to continue.',
            'acknowledge.in' => 'You must check the acknowledgment box to continue.',
        ]);

        $user = $request->user();
        if ($user && ! $user->nda_accepted_at) {
            $user->forceFill([
                'nda_accepted_at' => now(),
                'nda_accepted_ip' => $request->ip(),
            ])->save();
        }

        return redirect()
            ->intended(route('home'))
            ->with('status', 'Thank you. Your acknowledgment has been recorded. You may now continue to the private beta site.');
    }
}
