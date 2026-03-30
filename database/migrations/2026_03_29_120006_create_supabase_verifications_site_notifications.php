<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supabase public.notifications is implemented as site_notifications to avoid clashing
 * with Laravel's framework notifications table (morphs, uuid id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_notifications')) {
            Schema::create('site_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('message');
                $table->string('type');
                $table->string('related_id')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('vendor_capabilities_access_log')) {
            Schema::create('vendor_capabilities_access_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accessed_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_profile_id')->constrained('profiles')->cascadeOnDelete();
                $table->string('access_type');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['vendor_profile_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('vendor_verifications')) {
            Schema::create('vendor_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('profiles')->cascadeOnDelete();
                $table->string('verification_type');
                $table->string('status')->default('pending');
                $table->text('document_url')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('vendor_id');
            });
        }

        if (! Schema::hasTable('verification_attempts')) {
            Schema::create('verification_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->boolean('success')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_attempts');
        Schema::dropIfExists('vendor_verifications');
        Schema::dropIfExists('vendor_capabilities_access_log');
        Schema::dropIfExists('site_notifications');
    }
};
