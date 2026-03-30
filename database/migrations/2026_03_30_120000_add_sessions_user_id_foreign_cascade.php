<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy installs: sessions.user_id was indexed only (no FK). Enforce referential
 * integrity and cascade session rows when the owning user is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions') || ! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        $hasUserForeign = collect(Schema::getForeignKeys('sessions'))
            ->contains(fn (array $fk) => in_array('user_id', $fk['columns'] ?? [], true));

        if ($hasUserForeign) {
            return;
        }

        // Remove rows pointing at deleted users so the FK can be created.
        DB::table('sessions')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', DB::table('users')->select('id'))
            ->delete();

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        $hasUserForeign = collect(Schema::getForeignKeys('sessions'))
            ->contains(fn (array $fk) => in_array('user_id', $fk['columns'] ?? [], true));

        if (! $hasUserForeign) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
