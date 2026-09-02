<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('estimate_hash', 64)->unique();
            $table->string('tracking_token', 64)->unique();
            $table->json('scenario')->nullable();
            $table->json('result')->nullable();
            $table->string('status', 40)->default('estimate_completed');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('cta_clicked_at')->nullable();
            $table->timestamp('opportunity_started_at')->nullable();
            $table->timestamp('fee_initiated_at')->nullable();
            $table->timestamp('fee_completed_at')->nullable();
            $table->timestamp('vendor_selected_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('first_invoice_issued_at')->nullable();
            $table->timestamp('fee_credit_applied_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('estimate_follow_ups'); }
};
