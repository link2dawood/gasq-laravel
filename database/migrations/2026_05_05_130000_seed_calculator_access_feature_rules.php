<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $rules = [
        [
            'feature_key' => 'instant_estimator_access',
            'feature_name' => 'Instant Estimator – Vendor Access',
            'tokens_required' => 25,
            'description' => 'Vendor pays per session to open the Instant Estimator. Buyers and admins are not charged.',
        ],
        [
            'feature_key' => 'mobile_patrol_calculator_access',
            'feature_name' => 'Mobile Patrol Calculator – Vendor Access',
            'tokens_required' => 25,
            'description' => 'Vendor pays per session to open the Mobile Patrol Calculator.',
        ],
        [
            'feature_key' => 'mobile_patrol_hit_calculator_access',
            'feature_name' => 'Mobile Patrol Hit Calculator – Vendor Access',
            'tokens_required' => 25,
            'description' => 'Vendor pays per session to open the Mobile Patrol Hit Calculator.',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('feature_usage_rules')) {
            return;
        }

        foreach ($this->rules as $rule) {
            $exists = DB::table('feature_usage_rules')->where('feature_key', $rule['feature_key'])->exists();
            if (! $exists) {
                DB::table('feature_usage_rules')->insert(array_merge($rule, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('feature_usage_rules')) {
            return;
        }

        DB::table('feature_usage_rules')
            ->whereIn('feature_key', array_column($this->rules, 'feature_key'))
            ->delete();
    }
};
