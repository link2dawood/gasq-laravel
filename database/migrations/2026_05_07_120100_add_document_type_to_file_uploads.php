<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->string('document_type', 64)->nullable()->after('mime_type');
            $table->index(['user_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'document_type']);
            $table->dropColumn('document_type');
        });
    }
};
