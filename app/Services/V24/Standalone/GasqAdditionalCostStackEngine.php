<?php

namespace App\Services\V24\Standalone;

use Illuminate\Support\Arr;

class GasqAdditionalCostStackEngine
{
    /**
     * Merged view of Vehicle, Uniform/Equipment, Workforce Maintenance, and Training.
     * Defaults are chosen so that annual totals match the workbook excerpt at 21,322 hours.
     *
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $m = (array) ($scenario['meta'] ?? []);
        $hours = max(1.0, (float) Arr::get($m, 'annualBillableHours', 21322));

        $defaults = [
            // Use implied precision (annual / hours) so annual matches the sheet.
            'vehiclePatrolHourly' => 27953.13 / 21322.0,
            'uniformEquipmentHourly' => 24179.93 / 21322.0,
            'workforceMaintenanceHourly' => 86100.00 / 21322.0,
            'trainingHourly' => 73914.29 / 21322.0,
        ];

        $in = (array) Arr::get($m, 'hourly', []);
        $vehicle = (float) ($in['vehiclePatrol'] ?? $defaults['vehiclePatrolHourly']);
        $uniform = (float) ($in['uniformEquipment'] ?? $defaults['uniformEquipmentHourly']);
        $maint = (float) ($in['workforceMaintenance'] ?? $defaults['workforceMaintenanceHourly']);
        $training = (float) ($in['training'] ?? $defaults['trainingHourly']);

        $modules = [
            ['key' => 'vehiclePatrol', 'module' => 'Vehicle / Patrol', 'sourceTab' => 'Vehicle', 'hourly' => $vehicle],
            ['key' => 'uniformEquipment', 'module' => 'Uniform & Equipment', 'sourceTab' => 'Uniform_Equipment', 'hourly' => $uniform],
            ['key' => 'workforceMaintenance', 'module' => 'Workforce Maintenance', 'sourceTab' => 'Workforce_Maintenance_Module', 'hourly' => $maint],
            ['key' => 'training', 'module' => 'Training', 'sourceTab' => 'Training_Module', 'hourly' => $training],
        ];

        $rows = [];
        $totalHourly = 0.0;
        $totalAnnual = 0.0;

        foreach ($modules as $mod) {
            $h = max(0.0, (float) $mod['hourly']);
            $a = round($h * $hours, 2);
            $totalHourly += $h;
            $totalAnnual += $a;
            $rows[] = [
                'key' => $mod['key'],
                'module' => $mod['module'],
                'sourceTab' => $mod['sourceTab'],
                // Raw is used for recompute; display matches sheet formatting.
                'hourlyRaw' => $h,
                'hourly' => round($h, 2),
                'annual' => $a,
            ];
        }

        // Workbook excerpt shows a consolidated total row that may not equal the sum of
        // rounded annual rows (rounding / source-tab precision). Allow an override.
        $totalHourlyOverride = Arr::get($m, 'totals.hourly', null);
        $totalAnnualOverride = Arr::get($m, 'totals.annual', null);

        return [
            'title' => 'GASQ Additional Cost Stack',
            'subtitle' => 'Merged view of Vehicle, Uniform & Equipment, and Workforce Maintenance costs',
            'note' => 'Source tabs remain in the workbook for audit support. Additional Cost is the consolidated operating view.',
            'annualBillableHours' => round($hours, 2),
            'rows' => $rows,
            'totals' => [
                'hourlyRaw' => $totalHourly,
                'hourly' => $totalHourlyOverride !== null ? round((float) $totalHourlyOverride, 2) : round($totalHourly, 2),
                'annual' => $totalAnnualOverride !== null ? round((float) $totalAnnualOverride, 2) : round($totalAnnual, 2),
            ],
            'reference' => 'standalone:gasq-additional-cost-stack',
        ];
    }
}

