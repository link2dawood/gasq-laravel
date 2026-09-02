<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baseline_wage_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('buyer_baseline_wage', 8, 2);
            $table->decimal('recommended_baseline_wage', 8, 2)->nullable();
            $table->string('status', 32); // accepted | adjustment_requested | declined
            $table->json('reasons')->nullable();
            $table->text('explanation')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolution', 32)->nullable(); // approved | rejected
            $table->text('buyer_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['job_posting_id', 'vendor_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('baseline_wage_responses'); }
};
