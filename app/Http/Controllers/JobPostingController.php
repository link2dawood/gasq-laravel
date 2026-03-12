<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class JobPostingController extends Controller
{
    public function index(Request $request): View
    {
        $query = JobPosting::with(['user:id,name', 'bids' => fn ($q) => $q->with('user:id,name,company')]);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });

        $jobs = $query->latest()->paginate(15)->withQueryString();

        return view('jobs.index', compact('jobs'));
    }

    public function create(): View|RedirectResponse
    {
        if (! auth()->user()->isBuyer()) {
            return redirect()->route('job-board')->with('error', 'Only buyers can post jobs.');
        }
        return view('jobs.create');
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['special_requirements'] = $request->filled('special_requirements')
            ? array_filter(array_map('trim', explode("\n", $request->special_requirements)))
            : null;
        JobPosting::create($data);
        return redirect()->route('job-board')->with('success', 'Job posted successfully.');
    }

    public function show(JobPosting $job): View
    {
        $job->load(['user:id,name,company', 'bids.user:id,name,company']);
        return view('jobs.show', compact('job'));
    }

    public function edit(JobPosting $job): View|RedirectResponse
    {
        if ($job->user_id !== auth()->id()) {
            abort(403);
        }
        return view('jobs.edit', compact('job'));
    }

    public function update(StoreJobPostingRequest $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }
        $data = $request->validated();
        $data['special_requirements'] = $request->filled('special_requirements')
            ? array_filter(array_map('trim', explode("\n", $request->special_requirements)))
            : null;
        $job->update($data);
        return redirect()->route('jobs.show', $job)->with('success', 'Job updated.');
    }

    public function destroy(Request $request, JobPosting $job): RedirectResponse
    {
        if ($job->user_id !== $request->user()->id) {
            abort(403);
        }
        $job->delete();
        return redirect()->route('job-board')->with('success', 'Job removed.');
    }
}
