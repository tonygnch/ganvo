<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How a product's price is quoted: per piece, per square metre, per linear
 * metre, per cubic metre.
 *
 * Timber is not sold by the item. A merchant quoting 8,20 per square metre had
 * to either write "8.20" and hope the customer knew, or work out the price of
 * every size by hand and type it in. The unit makes the quote say what it
 * means, and lets a size's price be derived from its own dimensions.
 *
 * Defaults to 'piece', which is what every existing product is, so nothing
 * already in a catalogue changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('price_unit', 16)->default('piece')->after('price_cents');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('price_unit');
        });
    }
};
