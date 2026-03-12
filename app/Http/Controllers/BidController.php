<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBidRequest;
use App\Http\Requests\UpdateBidRequest;
use App\Models\Bid;
use App\Models\JobPosting;
use App\Notifications\BidNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BidController extends Controller
{
    public function store(StoreBidRequest $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id === $request->user()->id) {
            return back()->with('error', 'You cannot bid on your own job.');
        }
        if (Bid::where('job_posting_id', $job->id)->where('user_id', $request->user()->id)->exists()) {
            return back()->with('error', 'You have already submitted a bid for this job.');
        }
        $bid = Bid::create([
            'job_posting_id' => $job->id,
            'user_id' => $request->user()->id,
            'amount' => $request->amount,
            'message' => $request->message,
            'proposal' => $request->proposal,
            'status' => 'pending',
        ]);
        $job->user->notify(new BidNotification($bid, 'submitted'));
        return back()->with('success', 'Bid submitted successfully.');
    }

    public function update(UpdateBidRequest $request, Bid $bid): RedirectResponse
    {
        $bid->update([
            'amount' => $request->amount,
            'message' => $request->message,
            'proposal' => $request->proposal,
        ]);
        $bid->jobPosting->user->notify(new BidNotification($bid->fresh(), 'updated'));
        return back()->with('success', 'Bid updated.');
    }

    public function respond(Request $request, Bid $bid): RedirectResponse
    {
        $job = $bid->jobPosting;
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }
        $request->validate(['status' => ['required', 'in:accepted,rejected']]);
        $bid->update([
            'status' => $request->status,
            'responded_at' => now(),
        ]);
        $bid->user->notify(new BidNotification($bid->fresh(), $request->status === 'accepted' ? 'accepted' : 'rejected'));
        $message = $request->status === 'accepted' ? 'Bid accepted.' : 'Bid rejected.';
        return back()->with('success', $message);
    }

    public function counterOffer(Request $request, Bid $bid): RedirectResponse
    {
        $job = $bid->jobPosting;
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }
        if ($bid->status !== 'pending') {
            return back()->with('error', 'You can only send a counter offer for pending bids.');
        }
        $request->validate([
            'counter_offer_amount' => ['required', 'numeric', 'min:0'],
            'counter_offer_message' => ['nullable', 'string', 'max:2000'],
        ]);
        $bid->update([
            'counter_offer_amount' => $request->counter_offer_amount,
            'counter_offer_message' => $request->counter_offer_message,
            'counter_offer_at' => now(),
        ]);
        $bid->user->notify(new BidNotification($bid->fresh(), 'counter_offer'));
        return back()->with('success', 'Counter offer sent.');
    }
}
