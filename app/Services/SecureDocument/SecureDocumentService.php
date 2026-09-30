<?php

namespace App\Services\SecureDocument;

use App\Models\DocumentEvent;
use App\Models\DocumentRecipient;
use App\Models\DocumentVersion;
use App\Models\SecureDocument;
use App\Models\User;
use App\Support\SecureDocument as Flow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Creates documents, stores their files privately, adds recipients and issues
 * them. The file itself never lands on a public disk and is never linked to
 * directly: it is read back only through an authorised route.
 */
class SecureDocumentService
{
    /** The private disk. Its root is outside the web root. */
    public const DISK = 'local';

    public function __construct(private DocumentAccessService $access) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $creator, array $attributes): SecureDocument
    {
        $type = (string) ($attributes['document_type'] ?? 'other');

        return DB::transaction(function () use ($creator, $attributes, $type) {
            $document = SecureDocument::create(array_merge([
                'status' => Flow::DRAFT,
                'security_level' => Flow::LEVEL_OTP,
                'allow_download' => false,
                'allow_print' => false,
                'watermark_enabled' => true,
                'stakeholder_policy' => Flow::POLICY_GASQ_APPROVAL,
            ], $attributes, [
                'document_type' => $type,
                'public_id' => $this->nextPublicId($type),
                'created_by' => $creator->id,
            ]));

            $this->log($document, Flow::EVENT_CREATED, ['type' => $type], actor: $creator);

            return $document;
        });
    }

    /**
     * Public number: GASQ-APP-2026-00125. Sequential within a type and year,
     * and never reused, so a number always points at the same document.
     */
    public function nextPublicId(string $type): string
    {
        $prefix = 'GASQ-'.Flow::typePrefix($type).'-'.now()->format('Y').'-';

        $last = SecureDocument::query()
            ->where('public_id', 'like', $prefix.'%')
            ->orderByDesc('public_id')
            ->value('public_id');

        $sequence = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Store a new version. The previous one is marked superseded but stays
     * readable, and its activity stays attached to it.
     */
    public function addVersion(SecureDocument $document, UploadedFile $file, User $actor, ?string $changeNote = null): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $actor, $changeNote) {
            $next = (int) $document->versions()->max('version_number') + 1;

            $path = $file->store("secure-documents/{$document->id}", self::DISK);
            if (! $path) {
                throw new RuntimeException('The document could not be stored.');
            }

            $document->versions()->whereNull('superseded_at')->update(['superseded_at' => now()]);

            $version = $document->versions()->create([
                'version_number' => $next,
                'storage_key' => $path,
                'file_name' => $file->getClientOriginalName() ?: basename($path),
                'file_size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
                'change_note' => $changeNote,
                'created_by' => $actor->id,
            ]);

            $document->forceFill(['current_version_id' => $version->id])->save();

            if ($document->status === Flow::DRAFT) {
                $this->transition($document, Flow::READY, $actor);
            }

            $this->log($document, $next === 1 ? Flow::EVENT_UPLOADED : Flow::EVENT_VERSION_CREATED, [
                'version' => $next,
                'note' => $changeNote,
            ], version: $version, actor: $actor);

            return $version;
        });
    }

    /** Add someone who may open this document. Each gets their own access. */
    public function addRecipient(SecureDocument $document, array $attributes, ?User $actor = null, string $type = Flow::RECIPIENT_PRIMARY): DocumentRecipient
    {
        $email = mb_strtolower(trim((string) ($attributes['email'] ?? '')));
        if ($email === '') {
            throw new RuntimeException('A recipient needs an email address.');
        }

        $existing = $document->recipientFor($email);
        if ($existing) {
            return $existing;
        }

        return $document->recipients()->create(array_merge($attributes, [
            'email' => $email,
            'recipient_type' => $type,
            'access_status' => DocumentRecipient::INVITED,
            'added_by' => $actor?->id,
        ]));
    }

    /**
     * Issue the document: every recipient gets their own link.
     *
     * @return array<int, array{recipient: DocumentRecipient, url: string}>
     */
    public function send(SecureDocument $document, User $actor): array
    {
        if (! $document->currentVersion) {
            throw new RuntimeException('Upload a document before sending it.');
        }

        $links = [];
        foreach ($document->recipients()->whereNull('revoked_at')->get() as $recipient) {
            $links[] = [
                'recipient' => $recipient,
                'url' => $this->access->issueLink($document, $recipient)['url'],
            ];
        }

        if ($document->status !== Flow::SENT) {
            $this->transition($document, Flow::SENT, $actor);
        }
        $document->forceFill(['sent_at' => $document->sent_at ?? now()])->save();

        $this->log($document, Flow::EVENT_SENT, ['recipients' => count($links)], actor: $actor);

        return $links;
    }

    /** Stop all access immediately. Existing links stop working on the next request. */
    public function revoke(SecureDocument $document, User $actor, string $reason): SecureDocument
    {
        $document->forceFill([
            'revoked_at' => now(),
            'revoked_by' => $actor->id,
            'revocation_reason' => $reason,
        ])->save();

        $this->transition($document, Flow::REVOKED, $actor);
        $this->log($document, Flow::EVENT_ACCESS_REVOKED, ['reason' => $reason], actor: $actor);

        return $document->refresh();
    }

    public function restore(SecureDocument $document, User $actor): SecureDocument
    {
        $document->forceFill([
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null,
        ])->save();

        $this->transition($document, Flow::SENT, $actor);
        $this->log($document, Flow::EVENT_ACCESS_RESTORED, [], actor: $actor);

        return $document->refresh();
    }

    /** Move lifecycle status, or throw. Engagement never comes through here. */
    public function transition(SecureDocument $document, string $to, ?User $actor = null): SecureDocument
    {
        $from = (string) $document->status;

        if ($from !== $to && ! Flow::canTransition($from, $to)) {
            throw new RuntimeException("A document cannot go from {$from} to {$to}.");
        }

        $document->forceFill(['status' => $to])->save();

        return $document;
    }

    /** Read the stored file. Only ever called from an authorised route. */
    public function contents(DocumentVersion $version): string
    {
        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($version->storage_key)) {
            throw new RuntimeException('The stored document is missing.');
        }

        return (string) $disk->get($version->storage_key);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        SecureDocument $document,
        string $event,
        array $metadata = [],
        ?DocumentVersion $version = null,
        ?DocumentRecipient $recipient = null,
        ?User $actor = null,
    ): ?DocumentEvent {
        return $this->access->log($document, $event, $metadata, $version, $recipient, actor: $actor);
    }
}
