<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Final strict-parity step:
 *
 * 1. bids.estimated_start_date — column present in schem.sql, missing from all prior migrations.
 *
 * 2. job_postings: add nullable buyer_profile_id → profiles.id (backfilled via user_id).
 *    Existing user_id column is KEPT for app backward-compat; profile column is the Supabase mapping.
 *
 * 3. bids: add nullable vendor_profile_id → profiles.id (backfilled via user_id).
 *    Existing user_id column is KEPT for app backward-compat.
 *
 * 4. buyer_prequalification.profile_id: tighten from nullable → NOT NULL.
 *    Orphaned rows (user has no profile yet) are deleted first so the ALTER succeeds safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. bids.estimated_start_date
        if (Schema::hasTable('bids') && ! Schema::hasColumn('bids', 'estimated_start_date')) {
            Schema::table('bids', function (Blueprint $table) {
                $table->date('estimated_start_date')->nullable()->after('responded_at');
            });
        }

        // 2. job_postings.buyer_profile_id
        if (Schema::hasTable('job_postings') && Schema::hasTable('profiles')
            && ! Schema::hasColumn('job_postings', 'buyer_profile_id')
        ) {
            Schema::table('job_postings', function (Blueprint $table) {
                $table->foreignId('buyer_profile_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('profiles')
                    ->nullOnDelete();
            });

            foreach (DB::table('job_postings')->select('id', 'user_id')->cursor() as $row) {
                $profileId = DB::table('profiles')->where('user_id', $row->user_id)->value('id');
                if ($profileId !== null) {
                    DB::table('job_postings')->where('id', $row->id)->update(['buyer_profile_id' => $profileId]);
                }
            }
        }

        // 3. bids.vendor_profile_id
        if (Schema::hasTable('bids') && Schema::hasTable('profiles')
            && ! Schema::hasColumn('bids', 'vendor_profile_id')
        ) {
            Schema::table('bids', function (Blueprint $table) {
                $table->foreignId('vendor_profile_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('profiles')
                    ->nullOnDelete();
            });

            foreach (DB::table('bids')->select('id', 'user_id')->cursor() as $row) {
                $profileId = DB::table('profiles')->where('user_id', $row->user_id)->value('id');
                if ($profileId !== null) {
                    DB::table('bids')->where('id', $row->id)->update(['vendor_profile_id' => $profileId]);
                }
            }
        }

        // 4. Tighten buyer_prequalification.profile_id to NOT NULL
        if (Schema::hasTable('buyer_prequalification') && Schema::hasColumn('buyer_prequalification', 'profile_id')) {
            // Remove rows that could not be backfilled (no matching profile exists).
            DB::table('buyer_prequalification')->whereNull('profile_id')->delete();

            Schema::table('buyer_prequalification', function (Blueprint $table) {
                $table->foreignId('profile_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        // Reverse 4 — make profile_id nullable again
        if (Schema::hasTable('buyer_prequalification') && Schema::hasColumn('buyer_prequalification', 'profile_id')) {
            Schema::table('buyer_prequalification', function (Blueprint $table) {
                $table->foreignId('profile_id')->nullable()->change();
            });
        }

        // Reverse 3
        if (Schema::hasTable('bids') && Schema::hasColumn('bids', 'vendor_profile_id')) {
            Schema::table('bids', function (Blueprint $table) {
                $table->dropConstrainedForeignId('vendor_profile_id');
            });
        }

        // Reverse 2
        if (Schema::hasTable('job_postings') && Schema::hasColumn('job_postings', 'buyer_profile_id')) {
            Schema::table('job_postings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('buyer_profile_id');
            });
        }

        // Reverse 1
        if (Schema::hasTable('bids') && Schema::hasColumn('bids', 'estimated_start_date')) {
            Schema::table('bids', function (Blueprint $table) {
                $table->dropColumn('estimated_start_date');
            });
        }
    }
};
