<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_report_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('report_id')->unique();
            $table->char('report_hash', 64)->index();
            $table->string('signer_name', 120);
            $table->string('signer_title', 120)->nullable();
            $table->string('method', 16);
            $table->mediumText('signature_image')->nullable();
            $table->text('consent');
            $table->json('snapshot');
            $table->timestamp('signed_at');
            $table->index(['user_id', 'signed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_report_signatures');
    }
};
