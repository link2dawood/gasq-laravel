<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('calculator_type', 120);
            $table->json('scenario')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('last_ran_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'calculator_type']);
            $table->index(['user_id', 'last_ran_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_states');
    }
};
