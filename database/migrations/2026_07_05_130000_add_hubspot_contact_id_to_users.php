<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'hubspot_contact_id')) {
                // HubSpot CRM contact id, set once a user is synced. Durable key
                // that lets a future inbound (HubSpot → site) sync map back to us.
                $table->string('hubspot_contact_id')->nullable()->index()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'hubspot_contact_id')) {
                $table->dropColumn('hubspot_contact_id');
            }
        });
    }
};
