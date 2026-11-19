<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting files and photos a vendor attaches to an estimate: site
 * photographs, post orders, certificates of insurance, a signed scope.
 *
 * Files live on the private local disk, never under public/, because an
 * estimate and everything around it is confidential to that vendor and buyer.
 * The checksum lets a download prove it is serving the bytes that were stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional, and Laravel issues the table and its
        // index as separate statements. A first attempt whose index was
        // rejected leaves the table behind with the migration unrecorded, so
        // the retry would die on "table already exists". Recover only that
        // known, empty shell; never discard a table holding files.
        if (Schema::hasTable('vendor_estimate_attachments')) {
            if (! Schema::hasColumns('vendor_estimate_attachments', ['vendor_lead_estimate_id', 'path', 'sha256', 'original_name']) || DB::table('vendor_estimate_attachments')->exists()) {
                throw new RuntimeException('An existing estimate attachment table requires manual migration review; no records were changed.');
            }
            Schema::drop('vendor_estimate_attachments');
        }
        Schema::create('vendor_estimate_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_lead_estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->timestamps();
            // Named explicitly: the generated name would be 68 characters and
            // MySQL refuses any identifier over 64.
            $table->index(['vendor_lead_estimate_id', 'created_at'], 'vendor_est_attach_estimate_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_estimate_attachments');
    }
};
