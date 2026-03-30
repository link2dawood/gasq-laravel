<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Secondary indexes and extra uniques from Supabase (public.*).
 *
 * Skipped (already enforced by an existing unique / left-prefix index):
 * - bids.idx_bids_job_vendor — same columns as bids_job_posting_id_user_id_unique
 * - free_pool.idx_free_pool_month — same as unique(month)
 * - referral_tiers.idx_referral_tiers_level — same as unique(tier_level)
 * - wallets.idx_wallets_user_id — same as unique(user_id)
 * - user_tier_achievements.idx_user_tier_achievements_user_id — covered by unique(user_id, tier_id)
 *
 * Laravel bids use job_posting_id + user_id (Supabase: job_id + vendor_id).
 * Feed table: site_notifications; Supabase idx_notifications_* names applied there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analytics_events')) {
            $this->addIndex('analytics_events', ['created_at'], 'idx_analytics_events_created_at');
            $this->addIndex('analytics_events', ['user_id'], 'idx_analytics_events_user_id');
        }

        if (Schema::hasTable('conversations')) {
            if ($this->indexExists('conversations', 'conversations_buyer_id_vendor_id_index')) {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->dropIndex(['buyer_id', 'vendor_id']);
                });
            }
            $this->addUnique(
                'conversations',
                ['buyer_id', 'vendor_id', 'job_id'],
                'conversations_buyer_id_vendor_id_job_id_key'
            );
            $this->addIndex('conversations', ['buyer_id'], 'idx_conversations_buyer_id');
            $this->addIndex('conversations', ['vendor_id'], 'idx_conversations_vendor_id');
        }

        if (Schema::hasTable('free_pool_claims')) {
            $this->addUnique(
                'free_pool_claims',
                ['user_id', 'free_pool_id', 'claim_type'],
                'free_pool_claims_user_id_free_pool_id_claim_type_key'
            );
            $this->addIndex('free_pool_claims', ['user_id'], 'idx_free_pool_claims_user_id');
        }

        if (Schema::hasTable('job_matches')) {
            if ($this->indexExists('job_matches', 'job_matches_job_id_vendor_id_index')) {
                Schema::table('job_matches', function (Blueprint $table) {
                    $table->dropIndex(['job_id', 'vendor_id']);
                });
            }
            $this->addUnique(
                'job_matches',
                ['job_id', 'vendor_id'],
                'job_matches_job_id_vendor_id_key'
            );
            $this->addIndex('job_matches', ['job_id'], 'idx_job_matches_job_id');
            $this->addIndex('job_matches', ['vendor_id'], 'idx_job_matches_vendor_id');
        }

        if (Schema::hasTable('messages')) {
            $this->addIndex('messages', ['conversation_id'], 'idx_messages_conversation_id');
            $this->addIndex('messages', ['created_at'], 'idx_messages_created_at');
        }

        if (Schema::hasTable('site_notifications')) {
            $this->addIndex('site_notifications', ['created_at'], 'idx_notifications_created_at');
            $this->addIndex('site_notifications', ['user_id'], 'idx_notifications_user_id');
        }

        if (Schema::hasTable('referral_share_events')) {
            if ($this->indexExists('referral_share_events', 'referral_share_events_user_id_index')
                && ! $this->indexExists('referral_share_events', 'idx_referral_share_events_user_id')) {
                Schema::table('referral_share_events', function (Blueprint $table) {
                    $table->dropIndex(['user_id']);
                });
            }
            $this->addIndex('referral_share_events', ['sharing_method'], 'idx_referral_share_events_method');
            $this->addIndex('referral_share_events', ['user_id'], 'idx_referral_share_events_user_id');
        }

        if (Schema::hasTable('referrals')) {
            $this->addIndex('referrals', ['referral_code'], 'idx_referrals_code');
            $this->addIndex('referrals', ['referrer_user_id'], 'idx_referrals_referrer');
            $this->addIndex('referrals', ['sharing_method'], 'idx_referrals_sharing_method');
            $this->addIndex('referrals', ['status'], 'idx_referrals_status');
        }

        if (Schema::hasTable('saved_achievement_messages')) {
            $this->addIndex(
                'saved_achievement_messages',
                ['user_id', 'is_favorite'],
                'idx_saved_messages_favorite'
            );
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $this->addIndex('saved_achievement_messages', ['tags'], 'idx_saved_messages_tags');
            }
        }

        if (Schema::hasTable('transactions')) {
            $this->addIndex('transactions', ['created_at'], 'idx_transactions_created_at');
            $this->addIndex('transactions', ['user_id'], 'idx_transactions_user_id');
        }

        if (Schema::hasTable('user_tier_achievements')) {
            if ($this->indexExists('user_tier_achievements', 'user_tier_achievements_user_id_tier_id_index')) {
                Schema::table('user_tier_achievements', function (Blueprint $table) {
                    $table->dropIndex(['user_id', 'tier_id']);
                });
            }
            $this->addUnique(
                'user_tier_achievements',
                ['user_id', 'tier_id'],
                'user_tier_achievements_user_id_tier_id_key'
            );
        }

        if (Schema::hasTable('verification_codes')) {
            $this->addIndex(
                'verification_codes',
                ['user_id', 'type', 'status', 'expires_at'],
                'idx_verification_codes_user_type_status'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('verification_codes') && $this->indexExists('verification_codes', 'idx_verification_codes_user_type_status')) {
            Schema::table('verification_codes', function (Blueprint $table) {
                $table->dropIndex('idx_verification_codes_user_type_status');
            });
        }

        if (Schema::hasTable('user_tier_achievements')) {
            if ($this->indexExists('user_tier_achievements', 'user_tier_achievements_user_id_tier_id_key')) {
                Schema::table('user_tier_achievements', function (Blueprint $table) {
                    $table->dropUnique('user_tier_achievements_user_id_tier_id_key');
                });
                Schema::table('user_tier_achievements', function (Blueprint $table) {
                    $table->index(['user_id', 'tier_id']);
                });
            }
        }

        if (Schema::hasTable('transactions')) {
            foreach (['idx_transactions_created_at', 'idx_transactions_user_id'] as $indexName) {
                if ($this->indexExists('transactions', $indexName)) {
                    Schema::table('transactions', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }

        if (Schema::hasTable('saved_achievement_messages')) {
            foreach (['idx_saved_messages_favorite', 'idx_saved_messages_tags'] as $indexName) {
                if ($this->indexExists('saved_achievement_messages', $indexName)) {
                    Schema::table('saved_achievement_messages', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }

        if (Schema::hasTable('referrals')) {
            foreach (['idx_referrals_code', 'idx_referrals_referrer', 'idx_referrals_sharing_method', 'idx_referrals_status'] as $indexName) {
                if ($this->indexExists('referrals', $indexName)) {
                    Schema::table('referrals', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }

        if (Schema::hasTable('referral_share_events')) {
            foreach (['idx_referral_share_events_method', 'idx_referral_share_events_user_id'] as $indexName) {
                if ($this->indexExists('referral_share_events', $indexName)) {
                    Schema::table('referral_share_events', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
            if (! $this->indexExists('referral_share_events', 'referral_share_events_user_id_index')) {
                Schema::table('referral_share_events', function (Blueprint $table) {
                    $table->index(['user_id']);
                });
            }
        }

        if (Schema::hasTable('site_notifications')) {
            foreach (['idx_notifications_created_at', 'idx_notifications_user_id'] as $indexName) {
                if ($this->indexExists('site_notifications', $indexName)) {
                    Schema::table('site_notifications', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }

        if (Schema::hasTable('messages')) {
            foreach (['idx_messages_conversation_id', 'idx_messages_created_at'] as $indexName) {
                if ($this->indexExists('messages', $indexName)) {
                    Schema::table('messages', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }

        if (Schema::hasTable('job_matches')) {
            foreach (['idx_job_matches_job_id', 'idx_job_matches_vendor_id'] as $indexName) {
                if ($this->indexExists('job_matches', $indexName)) {
                    Schema::table('job_matches', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
            if ($this->indexExists('job_matches', 'job_matches_job_id_vendor_id_key')) {
                Schema::table('job_matches', function (Blueprint $table) {
                    $table->dropUnique('job_matches_job_id_vendor_id_key');
                });
                Schema::table('job_matches', function (Blueprint $table) {
                    $table->index(['job_id', 'vendor_id']);
                });
            }
        }

        if (Schema::hasTable('free_pool_claims')) {
            if ($this->indexExists('free_pool_claims', 'idx_free_pool_claims_user_id')) {
                Schema::table('free_pool_claims', function (Blueprint $table) {
                    $table->dropIndex('idx_free_pool_claims_user_id');
                });
            }
            if ($this->indexExists('free_pool_claims', 'free_pool_claims_user_id_free_pool_id_claim_type_key')) {
                Schema::table('free_pool_claims', function (Blueprint $table) {
                    $table->dropUnique('free_pool_claims_user_id_free_pool_id_claim_type_key');
                });
            }
        }

        if (Schema::hasTable('conversations')) {
            foreach (['idx_conversations_buyer_id', 'idx_conversations_vendor_id'] as $indexName) {
                if ($this->indexExists('conversations', $indexName)) {
                    Schema::table('conversations', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
            if ($this->indexExists('conversations', 'conversations_buyer_id_vendor_id_job_id_key')) {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->dropUnique('conversations_buyer_id_vendor_id_job_id_key');
                });
                Schema::table('conversations', function (Blueprint $table) {
                    $table->index(['buyer_id', 'vendor_id']);
                });
            }
        }

        if (Schema::hasTable('analytics_events')) {
            foreach (['idx_analytics_events_created_at', 'idx_analytics_events_user_id'] as $indexName) {
                if ($this->indexExists('analytics_events', $indexName)) {
                    Schema::table('analytics_events', function (Blueprint $table) use ($indexName) {
                        $table->dropIndex($indexName);
                    });
                }
            }
        }
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addIndex(string $tableName, array $columns, string $name): void
    {
        if ($this->indexExists($tableName, $name)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addUnique(string $tableName, array $columns, string $name): void
    {
        if ($this->indexExists($tableName, $name)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $name) {
            $table->unique($columns, $name);
        });
    }

    protected function indexExists(string $tableName, string $indexName): bool
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            if (strcasecmp($index['name'], $indexName) === 0) {
                return true;
            }
        }

        return false;
    }
};
