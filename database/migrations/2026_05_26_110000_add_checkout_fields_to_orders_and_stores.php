<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Shipping method snapshot. label keeps the receipt readable
            // even after the operator renames or removes the method.
            $table->string('shipping_method_label')->nullable()->after('shipping_address');
            $table->unsignedBigInteger('shipping_cents')->default(0)->after('shipping_method_label');
            // Customer-supplied extras at checkout.
            $table->string('customer_phone', 60)->nullable()->after('customer_name');
            $table->boolean('marketing_opt_in')->default(false)->after('customer_phone');
        });

        Schema::table('stores', function (Blueprint $table) {
            // Operator-defined shipping methods. JSON list of
            //   { label, description, price_cents, free_threshold_cents|null }
            // Store::shippingMethods() exposes a normalized view + a
            // built-in default when this is null (so brand-new stores
            // get a sensible Standard + Express out of the box).
            $table->json('shipping_methods')->nullable()->after('signup_fields');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_method_label', 'shipping_cents', 'customer_phone', 'marketing_opt_in']);
        });
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('shipping_methods');
        });
    }
};
