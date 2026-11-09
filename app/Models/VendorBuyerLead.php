<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorBuyerLead extends Model
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'won' => 'Won', 'lost' => 'Lost'];

    protected $fillable = ['vendor_id', 'contact_name', 'company', 'email', 'phone', 'site_address', 'service_type', 'scope', 'start_date', 'weekly_hours', 'status', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'weekly_hours' => 'decimal:2'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(VendorLeadEstimate::class);
    }
}
