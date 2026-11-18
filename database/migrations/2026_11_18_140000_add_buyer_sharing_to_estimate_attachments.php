<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supporting documents a vendor chooses to put in front of the buyer: a
 * contract, a scope of work, a risk assessment.
 *
 * Two pieces. The vendor marks an attachment for the buyer, and each send
 * takes its own copy of the marked files. The copy matters: a delivery is a
 * snapshot, so editing or deleting an attachment afterwards must not change
 * what a buyer was already given, exactly as the estimate PDF already works.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_estimate_attachments', function (Blueprint $table) {
            $table->boolean('share_with_buyer')->default(false)->after('sha256');
        });

        Schema::create('vendor_estimate_delivery_documents', function (Blueprint $table) {
            $table->id();
            // Both names given explicitly: generated from this table name they
            // would run past the 64-character MySQL identifier limit.
            $table->foreignId('vendor_estimate_delivery_id')
                ->constrained(indexName: 'vendor_est_doc_delivery_fk')
                ->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->timestamps();
            $table->index(['vendor_estimate_delivery_id', 'id'], 'vendor_est_doc_delivery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_estimate_delivery_documents');
        Schema::table('vendor_estimate_attachments', function (Blueprint $table) {
            $table->dropColumn('share_with_buyer');
        });
    }
};
