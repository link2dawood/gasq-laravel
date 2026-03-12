<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('tokens_change');
            $table->string('type', 50); // spend, purchase, bonus, etc.
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('balance_after')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
