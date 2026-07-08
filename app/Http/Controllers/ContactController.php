<?php

namespace App\Http\Controllers;

use App\Jobs\SyncContactToHubSpot;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('pages.contact');
    }

    public function submit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Persist first so a submission is never lost, even if mail delivery fails.
        if (Schema::hasTable('contact_messages')) {
            ContactMessage::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'],
                'user_id' => $request->user()?->id,
            ]);
        }

        // Capture the sender as a HubSpot contact (no-op until the token is set).
        SyncContactToHubSpot::dispatch(
            $request->user()?->id,
            $data['email'],
            [],
            ['name' => $data['name']],
        );

        $to = config('services.gasq.contact_email', 'info@getasecurityquotenow.com');

        try {
            Mail::raw(
                "New contact form submission\n\n"
                . "Name: {$data['name']}\n"
                . "Email: {$data['email']}\n"
                . 'Subject: ' . ($data['subject'] ?: '(none)') . "\n\n"
                . "Message:\n{$data['message']}\n",
                function ($message) use ($to, $data) {
                    $message->to($to)
                        ->replyTo($data['email'], $data['name'])
                        ->subject('GASQ Contact: ' . ($data['subject'] ?: 'New message'));
                }
            );
        } catch (\Throwable $e) {
            // Never block the user if mail delivery hiccups — log and move on.
            report($e);
        }

        return redirect()
            ->route('contact')
            ->with('success', "Thanks — your message has been sent. We'll get back to you shortly.");
    }
}
