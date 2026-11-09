<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_scheduling_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('timezone', 100)->default('UTC');
            $table->text('meeting_details');
            $table->timestamps();
        });
        Schema::create('vendor_review_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 100);
            $table->text('meeting_details');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['vendor_id', 'starts_at']);
            $table->index(['vendor_id', 'active', 'starts_at']);
        });
        Schema::create('vendor_review_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_lead_estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_estimate_delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_review_slot_id')->constrained()->cascadeOnDelete();
            // Nullable unique reservations allow canceled slots to be booked again.
            $table->unsignedBigInteger('active_slot_id')->nullable()->unique();
            $table->unsignedBigInteger('active_estimate_id')->nullable()->unique();
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 100);
            $table->text('meeting_details');
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_timezone', 100);
            $table->string('vendor_email');
            $table->string('vendor_name');
            $table->text('manage_token');
            $table->string('manage_token_hash', 64)->unique();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'status', 'starts_at']);
        });
        Schema::create('vendor_appointment_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_review_appointment_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('audience', 10);
            $table->string('recipient');
            $table->string('status', 20)->default('queued');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->timestamps();
            $table->unique(['vendor_review_appointment_id', 'kind', 'audience'], 'vendor_appointment_message_unique');
        });
        Schema::table('vendor_estimate_deliveries', function (Blueprint $table) {
            $table->boolean('native_scheduling')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('vendor_estimate_deliveries', fn (Blueprint $table) => $table->dropColumn('native_scheduling'));
        Schema::dropIfExists('vendor_appointment_messages');
        Schema::dropIfExists('vendor_review_appointments');
        Schema::dropIfExists('vendor_review_slots');
        Schema::dropIfExists('vendor_scheduling_profiles');
    }
};
