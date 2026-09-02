<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaselineWageResponse extends Model
{
    protected $fillable = ['job_posting_id','vendor_id','buyer_baseline_wage','recommended_baseline_wage','status','reasons','explanation','resolved_by','resolution','buyer_note','responded_at','resolved_at'];
    protected $casts = ['buyer_baseline_wage' => 'decimal:2', 'recommended_baseline_wage' => 'decimal:2', 'reasons' => 'array', 'responded_at' => 'datetime', 'resolved_at' => 'datetime'];
    public function jobPosting(): BelongsTo { return $this->belongsTo(JobPosting::class); }
    public function vendor(): BelongsTo { return $this->belongsTo(User::class, 'vendor_id'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
