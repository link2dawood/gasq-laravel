<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bids')) {
            return;
        }

        Schema::table('bids', function (Blueprint $table) {
            if (! Schema::hasColumn('bids', 'vendor_opportunity_invitation_id')) {
                $table->foreignId('vendor_opportunity_invitation_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('vendor_opportunity_invitations')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('bids', 'hourly_bill_rate')) {
                $table->decimal('hourly_bill_rate', 12, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('bids', 'weekly_price')) {
                $table->decimal('weekly_price', 12, 2)->nullable()->after('hourly_bill_rate');
            }
            if (! Schema::hasColumn('bids', 'monthly_price')) {
                $table->decimal('monthly_price', 12, 2)->nullable()->after('weekly_price');
            }
            if (! Schema::hasColumn('bids', 'annual_price')) {
                $table->decimal('annual_price', 12, 2)->nullable()->after('monthly_price');
            }
            if (! Schema::hasColumn('bids', 'staffing_plan')) {
                $table->text('staffing_plan')->nullable()->after('proposal');
            }
            if (! Schema::hasColumn('bids', 'start_availability')) {
                $table->string('start_availability', 255)->nullable()->after('staffing_plan');
            }
            if (! Schema::hasColumn('bids', 'vendor_notes')) {
                $table->text('vendor_notes')->nullable()->after('start_availability');
            }
            if (! Schema::hasColumn('bids', 'realism_score')) {
                $table->unsignedInteger('realism_score')->nullable()->after('vendor_notes');
            }
            if (! Schema::hasColumn('bids', 'realism_label')) {
                $table->string('realism_label', 50)->nullable()->after('realism_score');
            }
            if (! Schema::hasColumn('bids', 'realism_flagged')) {
                $table->boolean('realism_flagged')->default(false)->after('realism_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bids')) {
            return;
        }

        Schema::table('bids', function (Blueprint $table) {
            $columns = [
                'vendor_opportunity_invitation_id',
                'hourly_bill_rate',
                'weekly_price',
                'monthly_price',
                'annual_price',
                'staffing_plan',
                'start_availability',
                'vendor_notes',
                'realism_score',
                'realism_label',
                'realism_flagged',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bids', $column)) {
                    if ($column === 'vendor_opportunity_invitation_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
