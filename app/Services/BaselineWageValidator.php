<?php

namespace App\Services;

/**
 * Conservative baseline-wage assessment. Jurisdictional benchmarks can be supplied by
 * a future data provider; until then the federal floor prevents an unlawful release and
 * the service-specific threshold identifies staffing-continuity risk for buyer review.
 */
class BaselineWageValidator
{
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REVIEW_REQUIRED = 'review_required';
    public const STATUS_NOT_APPROVED = 'not_approved';

    /** @return array{status:string,legal_floor:float,recommended_wage:float,message:string} */
    public function assess(array $payload): array
    {
        $wage = is_numeric($payload['baseline_wage'] ?? null) ? (float) $payload['baseline_wage'] : 0.0;
        $legalFloor = 7.25;
        $service = strtolower(implode(' ', array_filter([
            (string) ($payload['category'] ?? ''),
            (string) ($payload['armed_status'] ?? ''),
            implode(' ', (array) ($payload['service_types'] ?? [])),
        ])));
        $recommended = str_contains($service, 'armed') ? 25.0 : (str_contains($service, 'mobile') ? 23.0 : 20.0);

        if ($wage < $legalFloor) {
            return ['status' => self::STATUS_NOT_APPROVED, 'legal_floor' => $legalFloor, 'recommended_wage' => $recommended,
                'message' => 'The baseline wage is below the applicable GASQ minimum eligibility floor and must be increased.'];
        }
        if ($wage < $recommended) {
            return ['status' => self::STATUS_REVIEW_REQUIRED, 'legal_floor' => $legalFloor, 'recommended_wage' => $recommended,
                'message' => 'The baseline wage may create recruiting, retention, overtime, call-off, or service-continuity risk.'];
        }

        return ['status' => self::STATUS_VALIDATED, 'legal_floor' => $legalFloor, 'recommended_wage' => $recommended,
            'message' => 'Baseline wage appears sustainable for the stated service assumptions.'];
    }
}
