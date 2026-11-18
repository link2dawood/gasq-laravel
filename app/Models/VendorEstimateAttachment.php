<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A supporting file or photo on a vendor estimate. Stored on the private local
 * disk and served only through a vendor-scoped download route.
 */
class VendorEstimateAttachment extends Model
{
    /** What a vendor may attach. Kept in step with the upload validation. */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'heic', 'heif', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    /** 15 MB, enough for a phone photo without inviting video. */
    public const MAX_KILOBYTES = 15360;

    /** Files per estimate. */
    public const MAX_PER_ESTIMATE = 20;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'share_with_buyer' => 'boolean'];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(VendorLeadEstimate::class, 'vendor_lead_estimate_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /** Human size, e.g. "2.4 MB". */
    public function readableSize(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
