<?php

namespace App\Services\V24\InstantEstimator;

use Illuminate\Support\Arr;

class InstantEstimatorComputeService
{
    public function __construct(
        private InstantEstimatorEngine $engine
    ) {}

    /**
     * @param  array<string, mixed>  $scenario
     * @return array<string, mixed>
     */
    public function compute(array $scenario): array
    {
        $meta = (array) ($scenario['meta'] ?? []);
        $posts = (array) ($scenario['posts'] ?? []);

        // Normalize minimal shape from UI
        $hours = (float) (Arr::get($meta, 'hoursPerWeek') ?? Arr::get($meta, 'hours') ?? 40);
        $guards = (float) (Arr::get($meta, 'guards') ?? 1);
        $location = (string) (Arr::get($meta, 'locationState') ?? Arr::get($meta, 'location') ?? 'california');
        $serviceType = (string) (Arr::get($meta, 'serviceType') ?? 'unarmed');

        if ($posts === []) {
            $posts = [[
                'postName' => 'Post 1',
                'positionTitle' => $serviceType,
                'weeklyHours' => $hours,
                'qtyRequired' => $guards,
            ]];
        }

        $kpis = $this->engine->compute([
            'meta' => [
                'locationState' => $location,
                'serviceType' => $serviceType,
            ],
            'posts' => $posts,
        ]);

        return [
            'kpis' => $kpis,
        ];
    }
}

