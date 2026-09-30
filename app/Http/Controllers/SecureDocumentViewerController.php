<?php

namespace App\Http\Controllers;

use App\Mail\SecureDocumentCodeMail;
use App\Models\DocumentRecipient;
use App\Models\DocumentSession;
use App\Models\SecureDocument;
use App\Services\SecureDocument\DocumentAccessService;
use App\Services\SecureDocument\SecureDocumentService;
use App\Support\SecureDocument as Flow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * The customer's side of a secure document. No account: a recipient arrives on
 * their own link and proves the address it was issued to.
 *
 * The file is never linked to directly. It is streamed by a route that checks
 * the session on every request, so a copied file URL is worth nothing.
 */
class SecureDocumentViewerController extends Controller
{
    public function __construct(
        private DocumentAccessService $access,
        private SecureDocumentService $documents,
    ) {}

    /** The secure link. Verified viewers go straight in; everyone else verifies. */
    public function open(Request $request, string $token): View|RedirectResponse
    {
        $resolved = $this->access->resolve($token);

        if (! $resolved['ok']) {
            return view('secure-documents.unavailable', [
                'reason' => $resolved['reason'],
                'message' => Flow::denialMessages()[$resolved['reason']] ?? Flow::denialMessages()['not_found'],
            ]);
        }

        $document = $resolved['document'];
        $recipient = $resolved['recipient'];

        $this->access->log($document, Flow::EVENT_LINK_CLICKED, [], recipient: $recipient);
        $request->session()->put('secure_document_token', $token);

        if ($this->verified($request, $document, $recipient)) {
            return redirect()->route('secure-documents.view');
        }

        return view('secure-documents.verify', [
            'document' => $document,
            'recipient' => $recipient,
            'maskedEmail' => $this->maskEmail($recipient->email),
        ]);
    }

    /** Email a code to the address the document was issued to. */
    public function sendCode(Request $request): RedirectResponse
    {
        [$document, $recipient, $denied] = $this->current($request);
        if ($denied) {
            return $denied;
        }

        $result = $this->access->sendCode($document, $recipient);
        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        Mail::to($recipient->email)->send(new SecureDocumentCodeMail($document, $recipient, $result['code']));

        return back()->with('status', 'We emailed a six-digit code to '.$this->maskEmail($recipient->email).'.');
    }

    /** Check the code, and the document password when one is set. */
    public function verify(Request $request): RedirectResponse
    {
        [$document, $recipient, $denied] = $this->current($request);
        if ($denied) {
            return $denied;
        }

        $request->validate([
            'code' => ['required', 'string', 'max:10'],
            'password' => [$document->requiresPassword() ? 'required' : 'nullable', 'string', 'max:100'],
        ]);

        $result = $this->access->verifyCode($document, $recipient, (string) $request->input('code'));
        if (! $result['ok']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        if ($document->requiresPassword()
            && ! $this->access->checkPassword($document, $recipient, (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'That document password is not correct.']);
        }

        $request->session()->put($this->sessionKey($document, $recipient), now()->addHours(DocumentAccessService::SESSION_HOURS)->timestamp);

        return redirect()->route('secure-documents.view');
    }

    /** The viewer itself. */
    public function view(Request $request): View|RedirectResponse
    {
        [$document, $recipient, $denied] = $this->current($request);
        if ($denied) {
            return $denied;
        }

        if (! $this->verified($request, $document, $recipient)) {
            return redirect()->route('secure-documents.open', $request->session()->get('secure_document_token'));
        }

        $session = $this->access->startSession($document, $recipient);
        $request->session()->put($this->viewSessionKey($document), $session->id);

        return view('secure-documents.viewer', [
            'document' => $document,
            'recipient' => $recipient,
            'version' => $document->currentVersion,
            'viewSession' => $session,
            'watermark' => $this->watermarkLines($document, $recipient),
        ]);
    }

    /**
     * The document bytes. Checked on every request, so the URL is useless to
     * anyone without the session, and it is never the storage location.
     */
    public function file(Request $request): Response|RedirectResponse
    {
        [$document, $recipient, $denied] = $this->current($request);
        if ($denied) {
            return $denied;
        }

        if (! $this->verified($request, $document, $recipient) || ! $document->currentVersion) {
            abort(403);
        }

        $contents = $this->documents->contents($document->currentVersion);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->public_id.'.pdf"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Heartbeat from the open viewer: counts reading time, not tab time. */
    public function heartbeat(Request $request): JsonResponse
    {
        [$document, $recipient, $denied] = $this->current($request);
        if ($denied) {
            return response()->json(['ok' => false], 403);
        }

        $validated = $request->validate([
            'seconds' => ['required', 'integer', 'min:0', 'max:300'],
            'page' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'closing' => ['nullable', 'boolean'],
        ]);

        $session = DocumentSession::find($request->session()->get($this->viewSessionKey($document)));
        if (! $session || $session->document_recipient_id !== $recipient->id) {
            return response()->json(['ok' => false], 409);
        }

        $this->access->heartbeat($session, (int) $validated['seconds']);

        if (! empty($validated['closing'])) {
            $this->access->endSession($session);
        }

        return response()->json(['ok' => true, 'active_seconds' => $session->fresh()->active_seconds]);
    }

    /** Someone holding a forwarded link asks to be let in. */
    public function requestAccess(Request $request, string $token): View|RedirectResponse
    {
        $resolved = $this->access->resolve($token);
        if (! $resolved['ok']) {
            return view('secure-documents.unavailable', [
                'reason' => $resolved['reason'],
                'message' => Flow::denialMessages()[$resolved['reason']] ?? Flow::denialMessages()['not_found'],
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
        ]);

        $outcome = $this->access->requestStakeholderAccess(
            $resolved['document'],
            $validated['email'],
            $validated['name'] ?? null,
            $validated['company'] ?? null,
        );

        return view('secure-documents.requested', [
            'document' => $resolved['document'],
            'outcome' => $outcome['outcome'],
            'email' => $validated['email'],
        ]);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * The document behind the session token.
     *
     * @return array{0: ?SecureDocument, 1: ?DocumentRecipient, 2: ?RedirectResponse}
     */
    private function current(Request $request): array
    {
        $token = (string) $request->session()->get('secure_document_token');
        if ($token === '') {
            return [null, null, redirect()->route('landing')];
        }

        $resolved = $this->access->resolve($token);
        if (! $resolved['ok']) {
            return [null, null, redirect()->route('secure-documents.open', $token)];
        }

        return [$resolved['document'], $resolved['recipient'], null];
    }

    private function verified(Request $request, SecureDocument $document, DocumentRecipient $recipient): bool
    {
        $expiry = $request->session()->get($this->sessionKey($document, $recipient));

        return is_numeric($expiry) && (int) $expiry > now()->timestamp;
    }

    private function sessionKey(SecureDocument $document, DocumentRecipient $recipient): string
    {
        return "secure_document_verified.{$document->id}.{$recipient->id}";
    }

    private function viewSessionKey(SecureDocument $document): string
    {
        return "secure_document_session.{$document->id}";
    }

    /** What appears across every page, so a screenshot stays traceable. */
    private function watermarkLines(SecureDocument $document, DocumentRecipient $recipient): array
    {
        return [
            'CONFIDENTIAL',
            $document->buyer_organization ? 'Prepared for '.$document->buyer_organization : null,
            'Viewed by '.$recipient->email,
            now()->format('F j, Y g:i A'),
            $document->public_id,
        ];
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1).str_repeat('*', max(1, mb_strlen($local) - 1)).'@'.$domain;
    }
}
