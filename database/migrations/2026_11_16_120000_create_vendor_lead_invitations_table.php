<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per private invitation a vendor sends, so the workflow has something to
 * report on.
 *
 * The vendor's invitation link is a single shared token, which cannot say WHICH buyer
 * opened it. Each invitation therefore carries its own `invite_key`, appended to the
 * link as a query parameter, and that is what attributes an open and a completed form
 * back to the buyer it was sent to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_lead_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_buyer_lead_id')->nullable()->constrained('vendor_buyer_leads')->nullOnDelete();
            $table->string('invite_key', 32)->unique();
            $table->string('buyer_name');
            $table->string('buyer_email');
            // The link token as it stood when the invitation went out, so a rotated
            // link still shows which one the buyer was given.
            $table->string('link_token', 64);
            $table->timestamp('sent_at');
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_lead_invitations');
    }
};
