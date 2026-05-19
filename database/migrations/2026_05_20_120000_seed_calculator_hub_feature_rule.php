<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $rule = [
        'feature_key' => 'calculator_hub_access',
        'feature_name' => 'Calculator Hub – Vendor Access',
        'tokens_required' => 25,
        'description' => 'Vendor pays per session to open the /calculator hub page. Buyers and admins are not charged.',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('feature_usage_rules')) {
            return;
        }

        $exists = DB::table('feature_usage_rules')
            ->where('feature_key', $this->rule['feature_key'])
            ->exists();

        if (! $exists) {
            DB::table('feature_usage_rules')->insert(array_merge($this->rule, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('feature_usage_rules')) {
            return;
        }

        DB::table('feature_usage_rules')
            ->where('feature_key', $this->rule['feature_key'])
            ->delete();
    }
};
