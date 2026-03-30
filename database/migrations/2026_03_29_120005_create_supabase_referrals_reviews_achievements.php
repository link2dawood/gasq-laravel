<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_tiers')) {
            Schema::create('referral_tiers', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('tier_level')->unique();
                $table->string('name');
                $table->unsignedInteger('referrals_required');
                $table->unsignedInteger('bonus_credits');
                $table->string('badge_icon');
                $table->string('badge_color');
                $table->json('perks')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('referral_code');
                $table->string('status')->default('pending'); // pending, completed
                $table->timestamp('completed_at')->nullable();
                $table->string('first_action_type')->nullable(); // job_post, bid
                $table->unsignedInteger('credits_awarded')->default(0);
                $table->string('sharing_method')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('referral_share_events')) {
            Schema::create('referral_share_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('referral_code');
                $table->string('sharing_method');
                $table->timestamp('created_at')->useCurrent();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('user_tier_achievements')) {
            Schema::create('user_tier_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tier_id')->constrained('referral_tiers')->cascadeOnDelete();
                $table->timestamp('achieved_at')->nullable();
                $table->boolean('bonus_awarded')->default(false);
                $table->index(['user_id', 'tier_id']);
            });
        }

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reviewer_id')->constrained('profiles')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('profiles')->cascadeOnDelete();
                $table->foreignId('job_id')->nullable()->constrained('job_postings')->nullOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->string('title')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->index(['vendor_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('vendor_ratings_summary')) {
            Schema::create('vendor_ratings_summary', function (Blueprint $table) {
                $table->foreignId('vendor_id')->primary()->constrained('profiles')->cascadeOnDelete();
                $table->unsignedInteger('total_reviews')->default(0);
                $table->decimal('average_rating', 4, 2)->default(0);
                $table->unsignedInteger('total_jobs_completed')->default(0);
                $table->decimal('response_rate', 6, 4)->default(0);
                $table->decimal('on_time_completion_rate', 6, 4)->default(0);
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('saved_achievement_messages')) {
            Schema::create('saved_achievement_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('message');
                $table->string('title')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_achievement_messages');
        Schema::dropIfExists('vendor_ratings_summary');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('user_tier_achievements');
        Schema::dropIfExists('referral_share_events');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_tiers');
    }
};
