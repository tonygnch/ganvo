<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whose drawing is this?
 *
 * Saved configurations were deduplicated across the whole tenant, so two
 * strangers who happened to build the same rack ended up sharing one row —
 * and the second one's save rewrote the first one's record. A share link was
 * therefore something any passer-by could edit.
 *
 * This column is the answer to "whose". It holds a SHA-256 of the session id,
 * never the id itself: it only ever has to be compared with a freshly computed
 * hash, so there is no reason for a database copy to hand out live sessions.
 *
 * Nullable, because every row that already exists predates the question, and
 * because a configuration is still perfectly valid without an owner — it just
 * cannot be reused by a later save, which is the safe direction to fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->string('owner_token', 64)->nullable()->after('tenant_id');

            // Every dedupe lookup is (tenant, owner, size) — see
            // ConfiguratorController::persist().
            $table->index(['tenant_id', 'owner_token'], 'rack_configs_tenant_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->dropIndex('rack_configs_tenant_owner_idx');
            $table->dropColumn('owner_token');
        });
    }
};
