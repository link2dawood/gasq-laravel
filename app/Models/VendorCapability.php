<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCapability extends Model
{
    protected $fillable = [
        'user_id',
        'profile_id',
        'legal_business_name',
        'dba_name',
        'business_structure',
        'business_address',
        'website',
        'duns_number',
        'uei_number',
        'cage_code',
        'business_license_number',
        'states_licensed',
        'core_competencies',
        'past_performance',
        'differentiators',
        'certifications',
        'naics_codes',
        'psc_codes',
        'general_liability_insurance',
        'professional_liability_insurance',
        'workers_comp_coverage',
        'bonding_capacity',
        'service_areas',
        'additional_info',
        'authorized_rep_name',
        'authorized_rep_title',
        'signature_date',
        'license_verified',
        'insurance_verified',
        'background_check_verified',
        'profile_completion_score',
        'years_of_experience',
        'team_size',
        'response_time',
        'availability_schedule',
    ];

    protected $casts = [
        'states_licensed' => 'array',
        'core_competencies' => 'array',
        'past_performance' => 'array',
        'certifications' => 'array',
        'naics_codes' => 'array',
        'psc_codes' => 'array',
        'service_areas' => 'array',
        'signature_date' => 'date',
        'license_verified' => 'boolean',
        'insurance_verified' => 'boolean',
        'background_check_verified' => 'boolean',
        'profile_completion_score' => 'integer',
        'years_of_experience' => 'integer',
        'availability_schedule' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
