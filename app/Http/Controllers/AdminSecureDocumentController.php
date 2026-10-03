<?php

namespace App\Http\Controllers;

use App\Mail\SecureDocumentSentMail;
use App\Models\DocumentAccessRequest;
use App\Models\DocumentRecipient;
use App\Models\SecureDocument;
use App\Services\SecureDocument\DocumentAccessService;
use App\Services\SecureDocument\SecureDocumentService;
use App\Support\SecureDocument as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * The GASQ side of the Secure Document Center: create, issue, watch and stop.
 *
 * Engagement figures here are read from the event trail rather than from the
 * document, so nothing a buyer does while reading can alter the document's own
 * state.
 */
class AdminSecureDocumentController extends Controller
{
    public function __construct(
        private SecureDocumentService $documents,
        private DocumentAccessService $access,
    ) {}

    public function index(Request $request): View
    {
        $documents = SecureDocument::query()
            ->with(['recipients', 'currentVersion'])
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('document_type', $t))
            ->when($request->string('q')->toString(), function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('public_id', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('buyer_organization', 'like', "%{$term}%")
                        ->orWhereHas('recipients', fn ($r) => $r->where('email', 'like', "%{$term}%"));
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.secure-documents.index', [
            'documents' => $documents,
            'pendingRequests' => DocumentAccessRequest::where('status', DocumentAccessRequest::PENDING)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.secure-documents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'document_type' => ['required', 'string', 'in:'.implode(',', array_keys(Flow::types()))],
            'buyer_organization' => ['nullable', 'string', 'max:160'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:25600'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'security_level' => ['required', 'string', 'in:'.implode(',', array_keys(Flow::securityLevels()))],
            'stakeholder_policy' => ['required', 'string', 'in:'.implode(',', array_keys(Flow::stakeholderPolicies()))],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'allow_download' => ['nullable', 'boolean'],
            'allow_print' => ['nullable', 'boolean'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $days = (int) ($validated['expires_in_days'] ?? 30);

        $document = $this->documents->create($request->user(), [
            'title' => $validated['title'],
            'document_type' => $validated['document_type'],
            'buyer_organization' => $validated['buyer_organization'] ?? null,
            'security_level' => $validated['security_level'],
            'stakeholder_policy' => $validated['stakeholder_policy'],
            'allow_download' => (bool) ($validated['allow_download'] ?? false),
            'allow_print' => (bool) ($validated['allow_print'] ?? false),
            'expires_at' => now()->addDays($days),
            'password_hash' => ! empty($validated['password']) ? password_hash($validated['password'], PASSWORD_DEFAULT) : null,
        ]);

        $this->documents->addVersion($document, $request->file('file'), $request->user());
        $this->documents->addRecipient($document, [
            'email' => $validated['recipient_email'],
            'name' => $validated['recipient_name'] ?? null,
            'company' => $validated['buyer_organization'] ?? null,
        ], $request->user());

        if (! empty($validated['send_now'])) {
            return $this->send($request, $document->refresh());
        }

        return redirect()->route('admin.secure-documents.show', $document)
            ->with('status', $document->public_id.' created. Send it when you are ready.');
    }

    public function show(SecureDocument $document): View
    {
        $document->load(['recipients.sessions', 'versions', 'currentVersion']);

        return view('admin.secure-documents.show', [
            'document' => $document,
            'events' => $document->events()->with('recipient')->limit(100)->get(),
            'engagement' => $this->engagement($document),
            'pages' => $this->pageAnalytics($document),
            'requests' => $document->accessRequests()->latest()->get(),
        ]);
    }

    /** Issue the document: each recipient gets their own link by email. */
    public function send(Request $request, SecureDocument $document): RedirectResponse
    {
        $links = $this->documents->send($document, $request->user());

        foreach ($links as $link) {
            Mail::to($link['recipient']->email)
                ->send(new SecureDocumentSentMail($document, $link['recipient'], $link['url']));
        }

        return redirect()->route('admin.secure-documents.show', $document)
            ->with('status', 'Sent to '.count($links).' recipient'.(count($links) === 1 ? '' : 's').'.');
    }

    public function addRecipient(Request $request, SecureDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $recipient = $this->documents->addRecipient($document, $validated, $request->user(), Flow::RECIPIENT_STAKEHOLDER);

        if ($document->status === Flow::SENT) {
            $url = $this->access->issueLink($document, $recipient)['url'];
            Mail::to($recipient->email)->send(new SecureDocumentSentMail($document, $recipient, $url));
        }

        return back()->with('status', $recipient->email.' can now request their own access.');
    }

    public function revokeRecipient(Request $request, SecureDocument $document, DocumentRecipient $recipient): RedirectResponse
    {
        abort_unless((int) $recipient->document_id === (int) $document->id, 404);

        $this->access->revokeRecipient($recipient, $request->user(), $request->input('reason'));

        return back()->with('status', 'Access withdrawn for '.$recipient->email.'.');
    }

    public function revoke(Request $request, SecureDocument $document): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        $this->documents->revoke($document, $request->user(), $validated['reason']);

        return back()->with('status', 'Access revoked. Every link stops working on the next request.');
    }

    public function restore(Request $request, SecureDocument $document): RedirectResponse
    {
        $this->documents->restore($document, $request->user());

        return back()->with('status', 'Access restored.');
    }

    /** Approve or deny someone who asked to be let in. */
    public function decideRequest(Request $request, DocumentAccessRequest $accessRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,deny'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        if ($validated['decision'] === 'approve') {
            $recipient = $this->access->approveStakeholder($accessRequest, $request->user());
            $url = $this->access->issueLink($accessRequest->document, $recipient)['url'];
            Mail::to($recipient->email)->send(new SecureDocumentSentMail($accessRequest->document, $recipient, $url));

            return back()->with('status', $recipient->email.' approved and sent their own link.');
        }

        $this->access->denyStakeholder($accessRequest, $request->user(), $validated['note'] ?? null);

        return back()->with('status', 'Request denied.');
    }

    /**
     * Engagement, derived from the events. Never stored on the document.
     *
     * @return array<string, mixed>
     */
    private function engagement(SecureDocument $document): array
    {
        $sessions = $document->sessions()->get();
        $recipients = $document->recipients;

        return [
            'first_viewed_at' => $recipients->whereNotNull('first_viewed_at')->min('first_viewed_at'),
            'last_viewed_at' => $recipients->max('last_viewed_at'),
            'sessions' => $sessions->count(),
            'active_seconds' => (int) $sessions->sum('active_seconds'),
            'viewers' => $recipients->where('session_count', '>', 0)->count(),
            'stakeholders' => $recipients->where('recipient_type', Flow::RECIPIENT_STAKEHOLDER)->count(),
            'downloads' => $document->events()->where('event_type', Flow::EVENT_DOWNLOAD_COMPLETED)->count(),
            'pages_read' => $document->pageViews()->distinct()->count('page_number'),
            'page_count' => $document->currentVersion?->page_count,
        ];
    }

    /**
     * Which pages held attention (spec 28). Totalled across every session, so
     * a page returned to on three visits reads as three.
     *
     * @return array<int, array{page: int, active_seconds: int, visits: int, readers: int}>
     */
    private function pageAnalytics(SecureDocument $document): array
    {
        return $document->pageViews()
            ->selectRaw('page_number, SUM(active_seconds) as active_seconds, SUM(view_count) as visits, COUNT(DISTINCT document_recipient_id) as readers')
            ->groupBy('page_number')
            ->orderBy('page_number')
            ->get()
            ->map(fn ($row) => [
                'page' => (int) $row->page_number,
                'active_seconds' => (int) $row->active_seconds,
                'visits' => (int) $row->visits,
                'readers' => (int) $row->readers,
            ])
            ->all();
    }
}
