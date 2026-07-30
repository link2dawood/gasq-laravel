<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            // Per-criterion scores captured on the weighted scorecard (spec §7).
            // capability_score (already present) stores the weighted 0-100 total.
            $table->json('score_breakdown')->nullable()->after('capability_score');
            $table->timestamp('scored_at')->nullable()->after('score_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['score_breakdown', 'scored_at']);
        });
    }
};
