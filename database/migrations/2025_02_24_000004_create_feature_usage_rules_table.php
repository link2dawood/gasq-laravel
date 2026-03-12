<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usage_rules', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key')->unique();
            $table->string('feature_name');
            $table->unsignedInteger('tokens_required')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage_rules');
    }
};
