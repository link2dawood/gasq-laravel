<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('job_postings')->nullOnDelete();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->index(['buyer_id', 'vendor_id']);
            });
        }

        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['conversation_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('job_matches')) {
            Schema::create('job_matches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('profiles')->cascadeOnDelete();
                $table->decimal('match_score', 12, 4)->nullable();
                $table->json('match_reasons')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
                $table->index(['job_id', 'vendor_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
