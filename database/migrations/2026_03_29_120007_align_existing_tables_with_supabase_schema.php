<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feature_usage_rules') && ! Schema::hasColumn('feature_usage_rules', 'role')) {
            Schema::table('feature_usage_rules', function (Blueprint $table) {
                $table->string('role', 20)->nullable()->after('tokens_required');
            });
        }

        if (Schema::hasTable('buyer_prequalification')) {
            if (! Schema::hasColumn('buyer_prequalification', 'qualified')) {
                Schema::table('buyer_prequalification', function (Blueprint $table) {
                    $table->boolean('qualified')->nullable()->after('requires_working_capital_45_60_days');
                });
            }

            if (! Schema::hasColumn('buyer_prequalification', 'profile_id')) {
                Schema::table('buyer_prequalification', function (Blueprint $table) {
                    $table->foreignId('profile_id')->nullable()->after('id')->constrained('profiles')->cascadeOnDelete();
                });

                foreach (DB::table('buyer_prequalification')->select('id', 'user_id')->cursor() as $row) {
                    $profileId = DB::table('profiles')->where('user_id', $row->user_id)->value('id');
                    if ($profileId !== null) {
                        DB::table('buyer_prequalification')->where('id', $row->id)->update(['profile_id' => $profileId]);
                    }
                }

                Schema::table('buyer_prequalification', function (Blueprint $table) {
                    $table->unique('profile_id');
                });
            }

            if (Schema::hasColumn('buyer_prequalification', 'qualified')) {
                foreach (DB::table('buyer_prequalification')->cursor() as $row) {
                    $qualified = (bool) $row->is_decision_maker && (bool) $row->has_approved_budget;
                    DB::table('buyer_prequalification')->where('id', $row->id)->update(['qualified' => $qualified]);
                }
            }
        }

        if (Schema::hasTable('vendor_capabilities') && ! Schema::hasColumn('vendor_capabilities', 'profile_id')) {
            Schema::table('vendor_capabilities', function (Blueprint $table) {
                $table->foreignId('profile_id')->nullable()->after('id')->constrained('profiles')->cascadeOnDelete();
            });

            foreach (DB::table('vendor_capabilities')->select('id', 'user_id')->cursor() as $row) {
                $profileId = DB::table('profiles')->where('user_id', $row->user_id)->value('id');
                if ($profileId !== null) {
                    DB::table('vendor_capabilities')->where('id', $row->id)->update(['profile_id' => $profileId]);
                }
            }

            Schema::table('vendor_capabilities', function (Blueprint $table) {
                $table->unique('profile_id');
            });
        }

        if (Schema::hasTable('vendor_capabilities')) {
            if (! Schema::hasColumn('vendor_capabilities', 'license_verified')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->boolean('license_verified')->default(false)->after('signature_date');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'insurance_verified')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->boolean('insurance_verified')->default(false)->after('license_verified');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'background_check_verified')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->boolean('background_check_verified')->default(false)->after('insurance_verified');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'profile_completion_score')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->unsignedInteger('profile_completion_score')->default(0)->after('background_check_verified');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'years_of_experience')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->unsignedInteger('years_of_experience')->nullable()->after('profile_completion_score');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'team_size')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->string('team_size')->nullable()->after('years_of_experience');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'response_time')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->string('response_time')->default('24 hours')->after('team_size');
                });
            }
            if (! Schema::hasColumn('vendor_capabilities', 'availability_schedule')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->json('availability_schedule')->nullable()->after('response_time');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_capabilities')) {
            if (Schema::hasColumn('vendor_capabilities', 'availability_schedule')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('availability_schedule');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'response_time')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('response_time');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'team_size')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('team_size');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'years_of_experience')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('years_of_experience');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'profile_completion_score')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('profile_completion_score');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'background_check_verified')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('background_check_verified');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'insurance_verified')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('insurance_verified');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'license_verified')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropColumn('license_verified');
                });
            }
            if (Schema::hasColumn('vendor_capabilities', 'profile_id')) {
                Schema::table('vendor_capabilities', function (Blueprint $table) {
                    $table->dropUnique(['profile_id']);
                    $table->dropConstrainedForeignId('profile_id');
                });
            }
        }

        if (Schema::hasTable('buyer_prequalification')) {
            if (Schema::hasColumn('buyer_prequalification', 'profile_id')) {
                Schema::table('buyer_prequalification', function (Blueprint $table) {
                    $table->dropUnique(['profile_id']);
                    $table->dropConstrainedForeignId('profile_id');
                });
            }
            if (Schema::hasColumn('buyer_prequalification', 'qualified')) {
                Schema::table('buyer_prequalification', function (Blueprint $table) {
                    $table->dropColumn('qualified');
                });
            }
        }

        if (Schema::hasTable('feature_usage_rules') && Schema::hasColumn('feature_usage_rules', 'role')) {
            Schema::table('feature_usage_rules', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
