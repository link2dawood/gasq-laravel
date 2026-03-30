<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('free_pool')) {
            Schema::create('free_pool', function (Blueprint $table) {
                $table->id();
                $table->string('month')->unique();
                $table->unsignedInteger('total_tokens');
                $table->unsignedInteger('tokens_used')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('free_pool_claims')) {
            Schema::create('free_pool_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('free_pool_id')->constrained('free_pool')->cascadeOnDelete();
                $table->unsignedInteger('tokens_granted');
                $table->string('claim_type'); // signup, first_rfp, first_bid, monthly
                $table->timestamp('created_at')->useCurrent();
                $table->index(['user_id', 'free_pool_id']);
            });
        }

        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2);
                $table->string('billing_interval'); // monthly, yearly
                $table->json('features')->nullable();
                $table->unsignedInteger('max_users')->default(1);
                $table->unsignedInteger('max_projects')->default(1);
                $table->boolean('is_active')->default(true);
                $table->string('stripe_price_id')->nullable();
                $table->string('stripe_product_id')->nullable();
                $table->decimal('one_time_price_multiplier', 8, 4)->default(0.01);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('token_packages')) {
            Schema::create('token_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('token_amount');
                $table->decimal('price', 12, 2);
                $table->boolean('is_premium')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('stripe_price_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->string('status')->default('active'); // active, cancelled, expired, trial
                $table->timestamp('starts_at')->useCurrent();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->string('stripe_subscription_id')->nullable();
                $table->string('stripe_customer_id')->nullable();
                $table->timestamps();
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role'); // admin, buyer, vendor, etc.
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['user_id', 'role']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('token_packages');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('free_pool_claims');
        Schema::dropIfExists('free_pool');
    }
};
