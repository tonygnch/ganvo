<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // The base currency the merchant prices in and gets paid in.
            $table->string('currency', 3)->default('USD')->after('font_family');
            // List of ISO codes customers can switch to in the storefront header.
            // Always implicitly includes the base currency.
            $table->json('display_currencies')->nullable()->after('currency');
            // { "EUR": 0.92, "GBP": 0.79 } — units of target per 1 unit of base.
            // Base currency always = 1.0 (implicit).
            $table->json('fx_rates')->nullable()->after('display_currencies');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['currency', 'display_currencies', 'fx_rates']);
        });
    }
};
