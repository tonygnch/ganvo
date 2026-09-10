<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rack configurator settings — one more merchant-authored JSON blob on stores,
 * alongside announcement / nav_menu / hero_banner / contact / about.
 *
 * Holds the VAT rate, the maximum buildable length, and the dimension sets the
 * configurator offers (heights, depths, section widths, level counts) together
 * with the nominal → real size mapping. All of it editable, because the
 * client's whole point (§19) is that none of this may live in code.
 *
 * `enabled` doubles as the feature flag: this is a Sankevi feature, and the
 * other tenants must never see the page or the admin screen. Gating on an
 * opt-in JSON key is exactly how the About page already works.
 *
 * A merchant who never opens it costs us a NULL, and Store::rackConfigurator()
 * supplies every default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('rack_configurator')->nullable()->after('shipping_methods');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('rack_configurator');
        });
    }
};
