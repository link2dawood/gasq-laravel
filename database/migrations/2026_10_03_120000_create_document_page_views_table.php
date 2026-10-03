<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page-level reading, one row per page per session (spec 28).
 *
 * Reading time is attributed to the page that was on screen when it was
 * counted, so "page 7 held them for two minutes" is a measurement rather than
 * an inference. Rows are per session, which keeps two things separable: how
 * long a page held someone this visit, and how often they came back to it.
 *
 * document_sessions gains last_page_number, which is what lets a return to an
 * earlier page be told apart from simply still being on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_page_views')) {
            Schema::create('document_page_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('document_recipient_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('page_number');
                $table->timestamp('started_at');
                // Counted the same way as a session's: active reading only.
                $table->unsignedInteger('active_seconds')->default(0);
                // Separate arrivals at this page within the one session.
                $table->unsignedSmallInteger('view_count')->default(1);
                $table->timestamps();

                $table->unique(['document_session_id', 'page_number'], 'document_page_view_unique');
                $table->index(['document_id', 'page_number'], 'document_page_view_doc_page_idx');
            });
        }

        if (Schema::hasTable('document_sessions') && ! Schema::hasColumn('document_sessions', 'last_page_number')) {
            Schema::table('document_sessions', function (Blueprint $table) {
                $table->unsignedSmallInteger('last_page_number')->nullable()->after('pages_viewed');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_page_views');

        if (Schema::hasTable('document_sessions') && Schema::hasColumn('document_sessions', 'last_page_number')) {
            Schema::table('document_sessions', function (Blueprint $table) {
                $table->dropColumn('last_page_number');
            });
        }
    }
};
