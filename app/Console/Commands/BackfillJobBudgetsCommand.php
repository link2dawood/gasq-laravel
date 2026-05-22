<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use App\Models\VendorOpportunity;
use App\Models\VendorOpportunityInvitation;
use App\Services\VendorOpportunityCreditPricingService;
use App\Services\VendorOpportunityQualificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillJobBudgetsCommand extends Command
{
    protected $signature = 'jobs:backfill-budgets
                            {--dry-run : Show what would change without writing}
                            {--all : Recompute every job, even ones with a non-zero contract value already set}';

    protected $description = 'Backfill annual_budget / budget_min / budget_max / opportunity contract value for jobs that were posted before the auto-budget fix.';

    private const SERVICE_DEFAULTS = [
        'unarmed' => 33.0,
        'armed' => 46.0,
        'supervisor' => 46.0,
        'mobile' => 46.0,
        'mobile_patrol' => 46.0,
        'patrol' => 46.0,
        'loss' => 46.0,
        'executive' => 60.0,
        'offduty' => 60.0,
        'off_duty' => 60.0,
        'off duty police officer' => 60.0,
        'roving patrol' => 46.0,
        'guards' => 33.0,
    ];

    public function handle(
        VendorOpportunityQualificationService $qualificationService,
        VendorOpportunityCreditPricingService $creditPricingService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $all = (bool) $this->option('all');

        $jobs = JobPosting::query()->orderBy('id')->get();
        $touched = 0;
        $skipped = 0;

        foreach ($jobs as $job) {
            $questionnaire = is_array($job->questionnaire_data) ? $job->questionnaire_data : [];
            $hasBudget = is_numeric($questionnaire['annual_budget'] ?? null)
                && (float) $questionnaire['annual_budget'] > 0;

            if (! $all && $hasBudget && (float) ($job->budget_max ?? 0) > 0) {
                $skipped++;
                continue;
            }

            $hoursPerDay = (float) ($questionnaire['hours_per_day'] ?? 0);
            $daysPerWeek = (float) ($questionnaire['days_per_week'] ?? 0);
            $weeksPerYear = (float) ($questionnaire['weeks_per_year'] ?? 0);
            $staffPerShift = max(1.0, (float) ($questionnaire['staff_per_shift'] ?? $job->guards_per_shift ?? 1));

            if ($hoursPerDay <= 0 || $daysPerWeek <= 0 || $weeksPerYear <= 0) {
                $this->warn("Job {$job->id} ({$job->title}) — skipped (missing scope inputs)");
                $skipped++;
                continue;
            }

            $baselineWage = $this->defaultBaselineWageForJob($questionnaire, $job);
            $employerCost = $baselineWage > 0 ? $baselineWage / 0.70 : 0.0;
            $annualEmployerCost = $employerCost * 3744;
            $internalTrueHourly = $annualEmployerCost > 0 ? $annualEmployerCost / 1456 : 0.0;
            $outsourcedHourly = $internalTrueHourly * 0.70;
            $weeklyCoverageHours = $hoursPerDay * $daysPerWeek * $staffPerShift;
            $annualCoverageHours = $weeklyCoverageHours * 52;
            $termCoverageHours = $weeklyCoverageHours * $weeksPerYear;

            $hourlyBudget = round($outsourcedHourly, 2);
            $annualBudget = round($outsourcedHourly * $annualCoverageHours, 2);
            $monthlyBudget = round($annualBudget / 12, 2);
            $termBudget = round($outsourcedHourly * $termCoverageHours, 2);

            $this->line(sprintf(
                'Job %d (%s) → baseline $%.2f/hr · annual $%s · term $%s',
                $job->id,
                $job->title,
                $baselineWage,
                number_format($annualBudget, 2),
                number_format($termBudget, 2),
            ));

            if ($dryRun) {
                $touched++;
                continue;
            }

            DB::transaction(function () use (
                $job, $questionnaire, $hourlyBudget, $monthlyBudget, $annualBudget, $termBudget,
                $qualificationService, $creditPricingService
            ): void {
                $questionnaire['hourly_budget'] = $hourlyBudget;
                $questionnaire['monthly_budget'] = $monthlyBudget;
                $questionnaire['annual_budget'] = $annualBudget;
                if (empty($questionnaire['budget_amount_range']) && $termBudget > 0) {
                    $questionnaire['budget_amount_range'] = '$' . number_format($termBudget, 2);
                }

                $job->questionnaire_data = $questionnaire;
                $job->budget_min = $annualBudget;
                $job->budget_max = $annualBudget;
                $job->save();

                $opportunity = VendorOpportunity::query()
                    ->where('job_posting_id', $job->id)
                    ->first();

                if ($opportunity) {
                    $qualification = $qualificationService->qualify($job->fresh());
                    $opportunity->estimated_annual_contract_value = $qualification['estimated_annual_contract_value'];
                    $opportunity->decision_maker_verified = $qualification['decision_maker_verified'];
                    $opportunity->budget_confirmed = $qualification['budget_confirmed'];
                    $opportunity->scope_completed = $qualification['scope_completed'];
                    $opportunity->timeline_ready = $qualification['timeline_ready'];
                    $opportunity->move_forward_confirmed = $qualification['move_forward_confirmed'];
                    $opportunity->save();

                    $creditCost = $creditPricingService->creditsFor(
                        (float) $opportunity->estimated_annual_contract_value,
                        (string) $opportunity->lead_tier,
                    );
                    VendorOpportunityInvitation::query()
                        ->where('vendor_opportunity_id', $opportunity->id)
                        ->update(['credits_to_unlock' => $creditCost]);
                }
            });

            $touched++;
        }

        $this->info(sprintf(
            '%s: %d job(s) %s · %d skipped',
            $dryRun ? 'DRY RUN' : 'DONE',
            $touched,
            $dryRun ? 'would be updated' : 'updated',
            $skipped,
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function defaultBaselineWageForJob(array $questionnaire, JobPosting $job): float
    {
        $candidates = [];
        $candidates[] = strtolower((string) ($questionnaire['service_type'] ?? ''));
        $candidates[] = strtolower((string) ($job->category ?? ''));
        foreach ((array) ($questionnaire['service_types'] ?? []) as $value) {
            if (is_string($value)) {
                $candidates[] = strtolower($value);
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') continue;
            if (isset(self::SERVICE_DEFAULTS[$candidate])) {
                return self::SERVICE_DEFAULTS[$candidate];
            }
            foreach (self::SERVICE_DEFAULTS as $key => $rate) {
                if (str_contains($candidate, $key)) {
                    return $rate;
                }
            }
        }

        return 33.0;
    }
}
