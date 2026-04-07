<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_input_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('inputs');
            $table->boolean('is_complete')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_complete']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_input_profiles');
    }
};

