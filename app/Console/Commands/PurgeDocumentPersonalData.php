<?php

namespace App\Console\Commands;

use App\Models\DocumentAccessRequest;
use App\Models\DocumentAccessToken;
use App\Models\DocumentEvent;
use App\Models\DocumentSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Forgets where people read from, once the retention window has passed
 * (spec 56).
 *
 * The distinction this command rests on: a document's history is permanent,
 * the reader's address is not. So nothing is deleted — every event, session and
 * request stays exactly where it was, and only the columns that point at a
 * person or a machine are cleared. A document activity report for a two-year-old
 * appraisal still says who opened it and when; it no longer says from where.
 *
 * Coarse device fields (device_type, browser, operating_system) are left alone.
 * They are buckets like "mobile" or "Chrome", which identify nobody, and the
 * page analytics read them. If GASQ decides those should go too, add them to
 * the session sweep below.
 */
class PurgeDocumentPersonalData extends Command
{
    protected $signature = 'documents:purge-personal-data {--dry-run : Report what would be cleared without changing anything}';

    protected $description = 'Clear viewer IP and user-agent data past the retention window';

    public function handle(): int
    {
        $months = (int) config('secure-documents.personal_data_retention_months');

        if ($months < 1) {
            $this->warn('Retention sweep is switched off (secure-documents.personal_data_retention_months is 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subMonths($months);
        $dryRun = (bool) $this->option('dry-run');

        $counts = [
            'events' => $this->sweepEvents($cutoff, $dryRun),
            'sessions' => $this->sweepSessions($cutoff, $dryRun),
            'tokens' => $this->sweepTokens($cutoff, $dryRun),
            'access requests' => $this->sweepRequests($cutoff, $dryRun),
        ];

        $total = array_sum($counts);
        $verb = $dryRun ? 'would clear' : 'cleared';

        if ($total === 0) {
            $this->info("Nothing older than {$cutoff->toDateString()} to clear.");

            return self::SUCCESS;
        }

        $this->info("Retention sweep {$verb} personal data from before {$cutoff->toDateString()}:");
        foreach ($counts as $label => $count) {
            if ($count > 0) {
                $this->line("  {$count} {$label}");
            }
        }

        return self::SUCCESS;
    }

    private function sweepEvents(Carbon $cutoff, bool $dryRun): int
    {
        $query = DocumentEvent::query()
            ->where('created_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNotNull('ip_address')->orWhereNotNull('user_agent'));

        return $dryRun
            ? $query->count()
            : $query->update(['ip_address' => null, 'user_agent' => null]);
    }

    private function sweepSessions(Carbon $cutoff, bool $dryRun): int
    {
        $query = DocumentSession::query()
            ->where('started_at', '<', $cutoff)
            ->whereNotNull('ip_address');

        return $dryRun
            ? $query->count()
            : $query->update(['ip_address' => null]);
    }

    /** A token is dated by its last use, or by when it was issued if never used. */
    private function sweepTokens(Carbon $cutoff, bool $dryRun): int
    {
        $query = DocumentAccessToken::query()
            ->whereNotNull('last_used_ip')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_used_at', '<', $cutoff)
                    ->orWhere(fn ($inner) => $inner->whereNull('last_used_at')->where('created_at', '<', $cutoff));
            });

        return $dryRun
            ? $query->count()
            : $query->update(['last_used_ip' => null]);
    }

    private function sweepRequests(Carbon $cutoff, bool $dryRun): int
    {
        $query = DocumentAccessRequest::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('ip_address');

        return $dryRun
            ? $query->count()
            : $query->update(['ip_address' => null]);
    }
}
