<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorQuestionnaireDocument extends Model
{
    protected $fillable = [
        'vendor_questionnaire_id',
        'file_upload_id',
        'document_type',
        'prefilled_from_profile',
    ];

    protected $casts = [
        'prefilled_from_profile' => 'boolean',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(VendorQuestionnaire::class, 'vendor_questionnaire_id');
    }

    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }
}
