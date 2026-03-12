<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->decimal('counter_offer_amount', 12, 2)->nullable()->after('responded_at');
            $table->text('counter_offer_message')->nullable()->after('counter_offer_amount');
            $table->timestamp('counter_offer_at')->nullable()->after('counter_offer_message');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn(['counter_offer_amount', 'counter_offer_message', 'counter_offer_at']);
        });
    }
};
