<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateFollowUp extends Model
{
    protected $fillable = ['user_id','email','estimate_hash','tracking_token','scenario','result','status','email_sent_at','opened_at','cta_clicked_at','opportunity_started_at','fee_initiated_at','fee_completed_at','vendor_selected_at','awarded_at','first_invoice_issued_at','fee_credit_applied_at'];
    protected $casts = ['scenario' => 'array', 'result' => 'array', 'email_sent_at' => 'datetime', 'opened_at' => 'datetime', 'cta_clicked_at' => 'datetime', 'opportunity_started_at' => 'datetime', 'fee_initiated_at' => 'datetime', 'fee_completed_at' => 'datetime', 'vendor_selected_at' => 'datetime', 'awarded_at' => 'datetime', 'first_invoice_issued_at' => 'datetime', 'fee_credit_applied_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
