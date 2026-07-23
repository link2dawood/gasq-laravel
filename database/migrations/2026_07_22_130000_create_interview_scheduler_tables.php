<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-job interview setup (buyer availability + method + sealed-price gate).
        Schema::create('interview_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('scheduling_method')->default('self');       // self | assigned | gasq
            $table->string('default_format')->nullable();               // virtual | phone | in_person
            $table->string('timezone')->nullable();
            $table->unsignedInteger('interview_minutes')->default(30);
            $table->unsignedInteger('evaluation_minutes')->default(15);
            $table->unsignedInteger('min_gap_minutes')->default(0);
            $table->string('location', 1000)->nullable();               // link or address
            $table->timestamp('scheduling_deadline')->nullable();
            $table->text('required_attendees')->nullable();
            $table->unsignedInteger('num_vendors')->nullable();
            $table->boolean('disclose_competitor_count')->default(false);
            // setup | open | interviews_complete | certified | price_revealed | closed
            $table->string('status')->default('setup');
            $table->timestamp('certified_at')->nullable();
            $table->string('reveal_method')->nullable();                // all | finalists | selected
            $table->timestamps();
        });

        // Buyer-published open slots for vendor self-scheduling.
        Schema::create('interview_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedBigInteger('interview_id')->nullable();     // booked by (no FK: avoids cycle)
            $table->timestamps();
            $table->index(['job_posting_id', 'starts_at']);
        });

        // One row per invited vendor — the interview + its lifecycle status.
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('bid_id')->nullable();
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->string('status')->default('invited');               // see Interview::STATUSES
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->string('format')->nullable();
            $table->string('location', 1000)->nullable();
            $table->string('timezone')->nullable();
            $table->decimal('capability_score', 6, 2)->nullable();
            $table->string('price_status')->default('sealed');          // sealed | revealed
            $table->timestamp('vendor_prep_acknowledged_at')->nullable();
            $table->unsignedInteger('reschedule_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->text('buyer_notes')->nullable();
            $table->timestamps();
            $table->unique(['job_posting_id', 'vendor_id']);
            $table->index(['job_posting_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('interview_slots');
        Schema::dropIfExists('interview_configs');
    }
};
