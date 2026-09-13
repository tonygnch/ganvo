<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who drew this rack — by name, not by session.
 *
 * owner_token answered "the same browser, still here", which was enough to stop
 * strangers overwriting each other and useless to the yard: a hashed session
 * cannot be phoned back. Saving now needs a customer account, and this column
 * is what lets the merchant's screen say whose rack it is.
 *
 * Nullable, because every rack saved before accounts were required has nobody
 * to point at — those read as „Гост" — and nullOnDelete, because removing a
 * customer must not take the drawing (or an order built from it) with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('owner_token')
                ->constrained()->nullOnDelete();

            // The account page and the reuse lookup both ask (tenant, customer).
            $table->index(['tenant_id', 'customer_id'], 'rack_configs_tenant_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->dropIndex('rack_configs_tenant_customer_idx');
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
