<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reset the platform to default to USD for everyone. The site had drifted to
 * Canadian (CA$) — either the global 'currency' Setting or per-user
 * `users.currency` preferences were set to CAD. This forces the global default
 * back to USD and clears per-user currency so everyone defaults to USD; users
 * (buyers AND vendors) can re-select another currency from the header switcher.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Global default → USD.
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(['key' => 'currency'], ['value' => 'USD']);
        }

        // Clear per-user currency so no account is stuck on CAD; USD is the default.
        if (Schema::hasColumn('users', 'currency')) {
            DB::table('users')->whereNotNull('currency')->update(['currency' => null]);
        }
    }

    public function down(): void
    {
        // Intentionally a no-op: we do not want to restore a CAD default.
    }
};
