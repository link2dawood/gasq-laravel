<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_lead_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamps();
        });
        Schema::create('vendor_buyer_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('contact_name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone', 50);
            $table->string('site_address', 500);
            $table->string('service_type', 100);
            $table->text('scope');
            $table->date('start_date')->nullable();
            $table->decimal('weekly_hours', 8, 2)->nullable();
            $table->string('status', 20)->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_buyer_leads');
        Schema::dropIfExists('vendor_lead_links');
    }
};
