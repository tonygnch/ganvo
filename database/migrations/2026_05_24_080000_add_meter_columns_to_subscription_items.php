<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashier 16.5+ requires meter_id + meter_event_name on subscription_items
 * (usage-based billing). Our original consolidated cashier migration
 * created the table without them; without these columns, every
 * Cashier::syncSubscriptionItems() call (triggered by swapAndInvoice +
 * by webhooks) throws "no column named meter_id" and the swap fails.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_items', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_items', 'meter_id')) {
                $table->string('meter_id')->nullable()->after('stripe_price');
            }
            if (! Schema::hasColumn('subscription_items', 'meter_event_name')) {
                $table->string('meter_event_name')->nullable()->after('meter_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_items', function (Blueprint $table) {
            $table->dropColumn(['meter_id', 'meter_event_name']);
        });
    }
};
