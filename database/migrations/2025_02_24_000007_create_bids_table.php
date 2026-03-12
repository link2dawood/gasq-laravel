<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // vendor
            $table->decimal('amount', 12, 2);
            $table->string('status', 50)->default('pending'); // pending, accepted, rejected
            $table->text('message')->nullable();
            $table->text('proposal')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['job_posting_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
