<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opportunity lifecycle status (review spec §28) and scope versioning (§6, P0-15).
 *
 * A separate `opportunity_status` rather than reusing `offer_status`: that column carries
 * only open/hired/closed_no_hire and is load-bearing in the hire flow, so overloading it
 * with a 14-state procurement lifecycle would break the award path.
 *
 * Scope versioning exists so a material change cannot silently alter an opportunity a
 * vendor has already accepted. Every bump is recorded with what changed and by whom.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_postings')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            if (! Schema::hasColumn('job_postings', 'opportunity_status')) {
                $table->string('opportunity_status', 40)->default('draft');
            }
            if (! Schema::hasColumn('job_postings', 'scope_version')) {
                $table->string('scope_version', 16)->default('1.0');
            }
            if (! Schema::hasColumn('job_postings', 'released_at')) {
                $table->timestamp('released_at')->nullable();
            }
        });

        Schema::create('scope_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();

            $table->string('version', 16);

            // Whether the change was material enough to require vendor re-acknowledgement.
            $table->boolean('is_material')->default(false);

            // Field-level record of what moved: [{field, label, from, to}, ...]
            $table->json('changes')->nullable();

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();

            $table->timestamps();
            $table->index(['job_posting_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_versions');

        if (! Schema::hasTable('job_postings')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table) {
            foreach (['opportunity_status', 'scope_version', 'released_at'] as $col) {
                if (Schema::hasColumn('job_postings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
