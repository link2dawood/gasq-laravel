<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // buyer
            $table->string('title');
            $table->string('category', 100)->nullable();
            $table->string('location')->nullable();
            $table->date('service_start_date')->nullable();
            $table->date('service_end_date')->nullable();
            $table->unsignedTinyInteger('guards_per_shift')->default(1);
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('property_type', 100)->nullable();
            $table->json('special_requirements')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
