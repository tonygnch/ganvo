<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A configured rack on an order: ONE order_item carrying the money, plus its
 * bill of materials in a child table.
 *
 * The BOM deliberately does not become forty order_items. `$order->items->
 * sum('subtotal_cents')` is load-bearing in the order confirmation and in the
 * OrderPlaced mail, the customer's receipt would read as a parts list rather
 * than a rack, and the merchant's editable items repeater would become forty
 * rows they could desynchronise from the total. One row holds the price; the
 * parts hang off it.
 *
 * Nor is the BOM read back off rack_configurations. A saved configuration can
 * be reopened and re-priced by anyone holding the link, which would rewrite
 * what a past order said it contained. An order's parts list is frozen here,
 * at the price and the sizes that applied on the day (§38), and survives the
 * configuration being deleted.
 *
 * rack_configuration_id is the link back to the live builder — "open this
 * customer's rack" — and is nullOnDelete precisely because the frozen copy
 * below is the record that matters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('rack_configuration_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::create('order_rack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            // Snapshotted, like order_items.product_name: the packing list must
            // still read accurately after the part is renamed or retired.
            $table->string('label', 160);
            $table->string('sku', 60)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('depth_cm')->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedInteger('quantity');
            // Ex-VAT, matching rack_parts. The VAT-inclusive figure lives once,
            // on the parent order_item.
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_rack_items');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['rack_configuration_id']);
            $table->dropColumn('rack_configuration_id');
        });
    }
};
