<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GASQ Secure Document Center — delivery and tracking for customer-facing PDFs.
 *
 * Three ideas shape this schema:
 *
 *   Access belongs to a recipient, not to a document. Every person who may open
 *   a document has their own row and their own token, so a forwarded link
 *   authorises nobody and revoking one person leaves the others alone.
 *
 *   Lifecycle and engagement are different things. documents.status answers
 *   "where is this document" and moves through a controlled set of values.
 *   How a buyer behaved is derived from document_events, which are never
 *   edited, so the two can never corrupt each other.
 *
 *   Versions are kept. A revision creates a new version and the previous one
 *   stays readable in the history with its own activity.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                // Shown to people: GASQ-APP-2026-00125. Never a database id.
                $table->string('public_id', 40)->unique();
                $table->string('document_type', 60)->index();
                $table->string('title');

                // What this document is about. All optional: a document can be
                // sent before an opportunity exists.
                $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('private_estimate_id')->nullable()->constrained()->nullOnDelete();
                $table->string('buyer_organization')->nullable();

                // Lifecycle only. Engagement is derived from the event trail.
                $table->string('status', 24)->default('draft')->index();
                $table->foreignId('current_version_id')->nullable();

                // Security settings, per document (spec 12, 16, 18, 32, 66).
                $table->string('security_level', 20)->default('otp');
                $table->boolean('allow_download')->default(false);
                $table->boolean('allow_print')->default(false);
                $table->boolean('watermark_enabled')->default(true);
                $table->string('stakeholder_policy', 30)->default('gasq_approval');
                $table->string('password_hash')->nullable();
                $table->timestamp('expires_at')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('revocation_reason')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['buyer_id', 'status']);
            });
        }

        if (! Schema::hasTable('document_versions')) {
            Schema::create('document_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version_number');
                // Path on the private disk. Never a public URL.
                $table->string('storage_key');
                $table->string('file_name');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedSmallInteger('page_count')->nullable();
                // Detects a file changed underneath us.
                $table->string('checksum', 64)->nullable();
                $table->text('change_note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('superseded_at')->nullable();
                $table->timestamps();

                $table->unique(['document_id', 'version_number'], 'document_version_unique');
            });
        }

        if (! Schema::hasTable('document_recipients')) {
            Schema::create('document_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('email');
                $table->string('company')->nullable();
                $table->string('job_title')->nullable();
                // primary · stakeholder · gasq · vendor
                $table->string('recipient_type', 20)->default('primary');
                // invited · verified · revoked · expired
                $table->string('access_status', 20)->default('invited');
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('first_viewed_at')->nullable();
                $table->timestamp('last_viewed_at')->nullable();
                $table->unsignedInteger('session_count')->default(0);
                $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->unique(['document_id', 'email'], 'document_recipient_unique');
            });
        }

        if (! Schema::hasTable('document_access_tokens')) {
            Schema::create('document_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('document_recipient_id')->constrained()->cascadeOnDelete();
                // Only the hash. A copy of this table cannot rebuild a link.
                $table->string('token_hash', 64)->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->string('last_used_ip', 45)->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_sessions')) {
            Schema::create('document_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('public_id', 40)->unique();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('document_recipient_id')->constrained()->cascadeOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                // Time the viewer was actually in front of the document, not
                // the time the tab was open.
                $table->unsignedInteger('active_seconds')->default(0);
                $table->unsignedSmallInteger('pages_viewed')->default(0);
                $table->string('device_type', 20)->nullable();
                $table->string('browser', 40)->nullable();
                $table->string('operating_system', 40)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['document_id', 'document_recipient_id']);
            });
        }

        if (! Schema::hasTable('document_events')) {
            Schema::create('document_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('document_recipient_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('document_session_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type', 60)->index();
                $table->unsignedSmallInteger('page_number')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['document_id', 'created_at'], 'document_events_doc_time_idx');
            });
        }

        if (! Schema::hasTable('document_access_requests')) {
            Schema::create('document_access_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->string('requester_email');
                $table->string('requester_name')->nullable();
                $table->string('requester_company')->nullable();
                // access · download
                $table->string('request_type', 20)->default('access');
                // pending · approved · denied · expired
                $table->string('status', 20)->default('pending')->index();
                $table->text('reason')->nullable();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('decided_at')->nullable();
                $table->string('decision_note')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['document_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_requests');
        Schema::dropIfExists('document_events');
        Schema::dropIfExists('document_sessions');
        Schema::dropIfExists('document_access_tokens');
        Schema::dropIfExists('document_recipients');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
