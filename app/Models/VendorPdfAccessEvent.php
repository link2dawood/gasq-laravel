<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPdfAccessEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function record(VendorEstimateDelivery $delivery, string $event, ?int $actorId = null): self
    {
        return self::create(['vendor_lead_estimate_id' => $delivery->vendor_lead_estimate_id, 'vendor_estimate_delivery_id' => $delivery->id, 'actor_id' => $actorId, 'event' => $event, 'pdf_sha256' => $delivery->pdf_sha256, 'ip_address' => app()->runningInConsole() ? null : request()->ip()]);
    }
}
