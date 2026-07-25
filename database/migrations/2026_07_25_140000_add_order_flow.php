<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enquiry orders — checkout without payment.
 *
 * Some merchants do not sell at a fixed shelf price: a sawmill quoting cut
 * timber prices the job after seeing the cutting list, so the storefront's
 * job is to capture a firm request, not to take money. Those orders are
 * reviewed and the customer is contacted.
 *
 * This replaces a genuinely dangerous default for such stores. Until now a
 * tenant without Stripe Connect fell into "stub" mode, which created orders
 * already marked PAID with paid_at stamped — a merchant could fulfil goods
 * nobody had paid for. An enquiry order is explicitly unpaid and stays that
 * way until the merchant resolves it.
 *
 * Snapshotted onto the order as well as the store, so changing the store
 * setting later never rewrites how an existing order should be read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('order_flow', 20)->default('payment')->after('checkout_mode');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_flow', 20)->default('payment')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('order_flow'));
        Schema::table('stores', fn (Blueprint $t) => $t->dropColumn('order_flow'));
    }
};
