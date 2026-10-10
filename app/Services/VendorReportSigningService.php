<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorReportSignature;
use App\Support\Currency;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorReportSigningService
{
    public const CONSENT = 'I have reviewed this saved vendor weighted-rate report and intend to sign it electronically.';

    public function __construct(private VendorWeightedRateReport $report) {}

    /** Hash the normalized content and identity that appear in this report. */
    public function document(array $scenario, User $user): array
    {
        $data = $this->report->prepare(['scenario' => $scenario, 'user' => $user]);

        return [
            'version' => 'GASQ weighted report v1.2',
            'ownerId' => $user->id,
            'vendorName' => $data['vendorName'],
            'buyerSite' => $data['buyerSite'],
            'preparedBy' => data_get($scenario, 'meta.preparedBy'),
            'currency' => isset(config('currency.profiles', [])[$user->currency ?? '']) ? $user->currency : Currency::code(),
            'selectionLabel' => $data['selectionLabel'],
            'mode' => $data['weighted']['mode'],
            'assumptions' => [
                'weeklyHoursPerEmployee' => (float) $data['weightedInputs']['weeklyHoursPerEmployee'],
                'weeksPerYear' => (float) $data['weightedInputs']['weeksPerYear'],
            ],
            'roles' => $data['weighted']['roles'],
            'totals' => $data['weighted']['totals'],
        ];
    }

    public function fingerprint(array $document): string
    {
        return hash('sha256', json_encode($document, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE));
    }

    public function status(array $scenario, User $user): array
    {
        $hash = $this->fingerprint($this->document($scenario, $user));
        $signature = VendorReportSignature::where('user_id', $user->id)->latest('id')->first();

        return [
            'reportHash' => $hash,
            'signatureStatus' => ! $signature ? 'unsigned' : (hash_equals($signature->report_hash, $hash) ? 'signed' : 'changed'),
            'signature' => $signature && hash_equals($signature->report_hash, $hash) ? [
                'reportId' => $signature->report_id,
                'signerName' => $signature->signer_name,
                'signerTitle' => $signature->signer_title,
                'signedAt' => $signature->signed_at->toIso8601String(),
                'method' => $signature->method,
            ] : null,
        ];
    }

    public function sign(array $scenario, User $user, array $input): VendorReportSignature
    {
        $document = $this->document($scenario, $user);
        $hash = $this->fingerprint($document);
        abort_unless(hash_equals($hash, $input['reportHash']), 409, 'The saved report changed. Review the recalculated report before signing.');
        $image = $input['method'] === 'drawn' ? $this->normalizeImage($input['signatureImage'] ?? '') : null;

        return VendorReportSignature::create([
            'user_id' => $user->id, 'report_id' => (string) Str::uuid(), 'report_hash' => $hash,
            'signer_name' => trim($input['signerName']), 'signer_title' => trim($input['signerTitle'] ?? '') ?: null,
            'method' => $input['method'], 'signature_image' => $image,
            'consent' => self::CONSENT,
            'snapshot' => ['document' => $document, 'accountName' => $user->name, 'accountEmail' => $user->email],
            'signed_at' => now(),
        ]);
    }

    public function active(array $scenario, User $user): ?VendorReportSignature
    {
        $status = $this->status($scenario, $user);
        if (! $status['signature']) {
            return null;
        }

        return VendorReportSignature::where('user_id', $user->id)->where('report_id', $status['signature']['reportId'])->first();
    }

    private function normalizeImage(string $data): string
    {
        $error = fn () => ValidationException::withMessages(['signatureImage' => ['Draw a signature in the signing area before signing.']]);
        if (! str_starts_with($data, 'data:image/png;base64,')) {
            throw $error();
        }
        $bytes = base64_decode(substr($data, 22), true);
        $info = $bytes === false ? false : @getimagesizefromstring($bytes);
        if (! $info || $info[2] !== IMAGETYPE_PNG || $info[0] > 1440 || $info[1] > 360 || $info[0] < 10 || $info[1] < 10) {
            throw $error();
        }
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            throw $error();
        }
        $clean = imagecreatetruecolor($info[0], $info[1]);
        imagefill($clean, 0, 0, imagecolorallocate($clean, 255, 255, 255));
        imagecopy($clean, $source, 0, 0, 0, 0, $info[0], $info[1]);
        imagedestroy($source);
        $ink = 0;
        for ($y = 0; $y < $info[1]; $y++) {
            for ($x = 0; $x < $info[0]; $x++) {
                $rgb = imagecolorat($clean, $x, $y);
                if ((($rgb >> 16) & 255) < 220 && (($rgb >> 8) & 255) < 220 && ($rgb & 255) < 220) {
                    $ink++;
                }
            }
        }
        if ($ink < 20) {
            imagedestroy($clean);
            throw $error();
        }
        ob_start();
        imagepng($clean);
        $png = ob_get_clean();
        imagedestroy($clean);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
