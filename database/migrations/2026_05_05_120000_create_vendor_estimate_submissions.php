<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('vendor_estimate_submissions')) {
            Schema::create('vendor_estimate_submissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_id');
                $table->unsignedBigInteger('job_posting_id');
                $table->unsignedBigInteger('buyer_id');
                $table->json('snapshot');
                $table->string('pdf_path')->nullable();
                $table->string('access_token', 64)->unique();
                $table->unsignedInteger('credits_spent')->default(0);
                $table->timestamp('emailed_at')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamps();

                $table->index('vendor_id');
                $table->index('job_posting_id');
                $table->index('buyer_id');
            });
        }

        if (Schema::hasTable('feature_usage_rules')) {
            $exists = DB::table('feature_usage_rules')->where('feature_key', 'vendor_estimate_submission')->exists();
            if (! $exists) {
                DB::table('feature_usage_rules')->insert([
                    'feature_key' => 'vendor_estimate_submission',
                    'feature_name' => 'Vendor Estimate Submission to Buyer',
                    'tokens_required' => 50,
                    'description' => 'Vendor submits their priced estimate directly to a buyer who posted a job. Includes emailed PDF and a dedicated customer page.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_estimate_submissions');

        if (Schema::hasTable('feature_usage_rules')) {
            DB::table('feature_usage_rules')->where('feature_key', 'vendor_estimate_submission')->delete();
        }
    }
};
