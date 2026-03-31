<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('verification_codes', 'code_hash')) {
                $table->string('code_hash')->nullable()->after('code');
            }
            if (! Schema::hasColumn('verification_codes', 'attempts')) {
                $table->unsignedSmallInteger('attempts')->default(0)->after('status');
            }
            if (! Schema::hasColumn('verification_codes', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('verification_codes', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_codes', function (Blueprint $table) {
            if (Schema::hasColumn('verification_codes', 'code_hash')) {
                $table->dropColumn('code_hash');
            }
            if (Schema::hasColumn('verification_codes', 'attempts')) {
                $table->dropColumn('attempts');
            }
            if (Schema::hasColumn('verification_codes', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
            if (Schema::hasColumn('verification_codes', 'last_sent_at')) {
                $table->dropColumn('last_sent_at');
            }
        });
    }
};

