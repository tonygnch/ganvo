<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How much the customer asked for, in the product's own unit.
 *
 * A shopper buying paneling states an area — "20 m²" — not a number of boards.
 * `quantity` stays what it has always been, a count of pieces; this records
 * what was actually requested, so the enquiry that reaches the merchant says
 * twenty square metres rather than leaving them to infer it.
 *
 * Nullable, and null for every product sold by the piece — which is all of them
 * until a merchant sets a price unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            // 10,3 — three decimals is finer than anyone orders timber to, and
            // leaves room for cubic metres where the numbers are small.
            $table->decimal('measure_quantity', 10, 3)->nullable()->after('quantity');
            $table->string('measure_unit', 8)->nullable()->after('measure_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn(['measure_quantity', 'measure_unit']);
        });
    }
};
