<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A supporting document as it was sent with one delivery.
 *
 * Its own copy of the bytes, taken when the estimate was emailed, so what the
 * buyer downloads is what the vendor sent and nothing later can rewrite it.
 * Served only from the buyer access page, after email verification, and
 * withdrawn when that delivery's access is revoked or expires.
 */
class VendorEstimateDeliveryDocument extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(VendorEstimateDelivery::class, 'vendor_estimate_delivery_id');
    }

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
