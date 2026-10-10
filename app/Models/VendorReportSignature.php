<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorReportSignature extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'report_id', 'report_hash', 'signer_name', 'signer_title', 'method', 'signature_image', 'consent', 'snapshot', 'signed_at'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'signed_at' => 'immutable_datetime'];
    }
}
