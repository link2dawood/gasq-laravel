<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('nda_accepted_at')->nullable()->after('phone_verified');
            $table->string('nda_accepted_ip', 45)->nullable()->after('nda_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nda_accepted_at', 'nda_accepted_ip']);
        });
    }
};
