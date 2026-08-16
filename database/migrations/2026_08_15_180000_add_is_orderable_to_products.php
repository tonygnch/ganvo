<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a product can be ORDERED, as distinct from whether it is SHOWN.
 *
 * `is_active` already answers the second question, and merchants were using it
 * for the first: a board they still make but cannot take online orders for —
 * priced on the day, cut to spec, sold by the lorry-load — had to be hidden
 * from the catalogue entirely to stop people buying it. That loses the listing,
 * the photograph and the search hit along with the button.
 *
 * Default TRUE, so every product that exists today keeps behaving exactly as it
 * did and the column changes no page until a merchant turns one off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_orderable')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_orderable');
        });
    }
};
