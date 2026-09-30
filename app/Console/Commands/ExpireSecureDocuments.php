<?php

namespace App\Console\Commands;

use App\Models\SecureDocument;
use App\Services\SecureDocument\SecureDocumentService;
use App\Support\SecureDocument as Flow;
use Illuminate\Console\Command;

/**
 * Closes access to documents whose validity has run out (spec 13).
 *
 * Expiry is already enforced when someone tries to open a document; this moves
 * the stored status to match, so the dashboard and filters tell the truth
 * without waiting for someone to click a dead link.
 */
class ExpireSecureDocuments extends Command
{
    protected $signature = 'documents:expire';

    protected $description = 'Mark secure documents whose access period has ended';

    public function handle(SecureDocumentService $documents): int
    {
        $expired = 0;

        SecureDocument::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where('status', Flow::SENT)
            ->each(function (SecureDocument $document) use ($documents, &$expired) {
                $documents->transition($document, Flow::EXPIRED);
                $documents->log($document, Flow::EVENT_ACCESS_EXPIRED, [
                    'expired_at' => $document->expires_at?->toIso8601String(),
                ]);
                $expired++;
            });

        $this->info($expired === 0 ? 'No documents to expire.' : "Expired {$expired} document(s).");

        return self::SUCCESS;
    }
}
