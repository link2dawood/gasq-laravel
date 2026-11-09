<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendorWorkflowPreflight extends Command
{
    protected $signature = 'vendor-workflow:preflight {--production : Enforce production environment, HTTPS, and debug settings}';

    protected $description = 'Check vendor workflow migrations, private storage, encryption, mail, and queue configuration without sending email';

    public function handle(): int
    {
        $passed = true;
        $check = function (string $label, bool $ok) use (&$passed) {
            $this->line(($ok ? 'PASS ' : 'FAIL ').$label);
            $passed = $passed && $ok;
        };
        try {
            foreach (['vendor_lead_links', 'vendor_buyer_leads', 'vendor_lead_estimates', 'vendor_estimate_deliveries', 'vendor_scheduling_profiles', 'vendor_review_slots', 'vendor_review_appointments', 'vendor_appointment_messages', 'vendor_pdf_access_events'] as $table) {
                $check('Table '.$table, Schema::hasTable($table));
            }
            $check('Password-release migration', Schema::hasColumn('vendor_estimate_deliveries', 'released_password'));
        } catch (\Throwable) {
            $check('Database connection', false);
        }
        try {
            $plain = Str::random(32);
            $check('Application encryption key', Crypt::decryptString(Crypt::encryptString($plain)) === $plain);
        } catch (\Throwable) {
            $check('Application encryption key', false);
        }
        $path = 'workflow-preflight/'.Str::uuid().'.txt';
        try {
            $disk = Storage::disk('local');
            $root = rtrim(realpath($disk->path('')) ?: $disk->path(''), DIRECTORY_SEPARATOR);
            $public = rtrim(realpath(public_path()) ?: public_path(), DIRECTORY_SEPARATOR);
            $check('Private local storage', config('filesystems.disks.local.driver') === 'local' && $root !== $public && ! str_starts_with($root, $public.DIRECTORY_SEPARATOR));
            $check('Private file write/read', $disk->put($path, 'vendor-workflow-probe') && $disk->get($path) === 'vendor-workflow-probe');
        } catch (\Throwable) {
            $check('Private local storage', false);
        } finally {
            try {
                Storage::disk('local')->delete($path);
            } catch (\Throwable) {
            }
        }
        $mailer = config('mail.default');
        $check('Live mail transport configured', $this->liveMailer($mailer));
        $check('Queue connection configured', ! in_array(config('queue.default'), [null, '', 'null'], true));
        if ($this->option('production')) {
            $check('Production environment', app()->environment('production'));
            $check('Debug disabled', ! config('app.debug'));
            $check('HTTPS application URL', parse_url(config('app.url'), PHP_URL_SCHEME) === 'https');
            $check('Secure session cookie', (bool) config('session.secure'));
        }
        $this->line('Operational checks still required: mail-provider acceptance, queue worker, minutely schedule:run, database backup, HTTPS, and a real buyer test.');
        $this->line('No email sent. No credentials printed. Offline PDF openings cannot be tracked.');

        return $passed ? self::SUCCESS : self::FAILURE;
    }

    private function liveMailer(?string $name, array $seen = []): bool
    {
        if (! $name || in_array($name, $seen, true)) {
            return false;
        }
        $config = config('mail.mailers.'.$name, []);
        $transport = $config['transport'] ?? '';
        if (in_array($transport, ['', 'log', 'array'], true)) {
            return false;
        }
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $config['mailers'] ?? [];
            foreach ($children as $child) {
                if (! $this->liveMailer($child, [...$seen, $name])) {
                    return false;
                }
            }

            return count($children) > 0;
        }

        return true;
    }
}
