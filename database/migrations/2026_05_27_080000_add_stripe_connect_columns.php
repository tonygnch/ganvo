<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tenants.stripe_account_id already exists (since the original
        // tenant migration) and is exactly the right shape to hold the
        // Connect account id — we just start populating it once a
        // merchant onboards via Connect. Add the rest of the Connect
        // state alongside it.
        Schema::table('tenants', function (Blueprint $table) {
            // 'express' for now; 'standard' once we add OAuth onboarding.
            // Null means no Connect account at all.
            $table->string('stripe_connect_account_type', 20)->nullable()->after('stripe_account_id');
            // Synced from Stripe via the account.updated webhook +
            // explicit syncFromStripe() pulls. Defaults make the gate
            // closed by default: stub mode until proven ready.
            $table->boolean('stripe_connect_charges_enabled')->default(false)->after('stripe_connect_account_type');
            $table->boolean('stripe_connect_payouts_enabled')->default(false)->after('stripe_connect_charges_enabled');
            $table->boolean('stripe_connect_details_submitted')->default(false)->after('stripe_connect_payouts_enabled');
            // Stripe surfaces a string reason when an account is
            // restricted (e.g. 'requirements.past_due'). Surfaced in
            // the StoreAdmin Payments page so the operator knows what
            // to fix.
            $table->string('stripe_connect_disabled_reason')->nullable()->after('stripe_connect_details_submitted');
            // Per-tenant fee override in basis points (200 = 2%). Null
            // means "use plan default". Stored as smallint to make the
            // intent clear (max 32767 bps = 327%, well past anything
            // sane); the effective rate is clamped at compute time.
            $table->smallInteger('platform_fee_bps')->nullable()->after('stripe_connect_disabled_reason');

            // Index on charges_enabled so SA dashboards can quickly
            // count "live merchants" without a full scan.
            $table->index('stripe_connect_charges_enabled');
        });

        // Plan-level default fee. 0 = no fee on transactions for
        // tenants on this plan (the safe default while we figure out
        // pricing). Operator sets this in the SA Plan resource.
        Schema::table('plans', function (Blueprint $table) {
            $table->smallInteger('platform_fee_bps')->default(0)->after('discount_percent');
        });

        // Order-side: snapshot the payment method + Stripe ids + the
        // fee we collected. Snapshotting platform_fee_cents (rather
        // than recomputing from rate * total) means historic orders
        // stay accurate even if rates change later.
        Schema::table('orders', function (Blueprint $table) {
            // 'stub' = legacy/demo behavior (current default), 'stripe'
            // = real PaymentIntent. Extensible to other gateways later.
            $table->string('payment_method', 20)->default('stub')->after('status');
            $table->string('stripe_charge_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('stripe_application_fee_id')->nullable()->after('stripe_charge_id');
            $table->unsignedBigInteger('platform_fee_cents')->default(0)->after('stripe_application_fee_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['stripe_connect_charges_enabled']);
            $table->dropColumn([
                'stripe_connect_account_type',
                'stripe_connect_charges_enabled',
                'stripe_connect_payouts_enabled',
                'stripe_connect_details_submitted',
                'stripe_connect_disabled_reason',
                'platform_fee_bps',
            ]);
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('platform_fee_bps');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'stripe_charge_id',
                'stripe_application_fee_id',
                'platform_fee_cents',
            ]);
        });
    }
};
