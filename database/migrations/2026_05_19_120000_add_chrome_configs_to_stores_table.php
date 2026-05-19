<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Editable announcement bar at the top of every theme.
            //   { enabled: bool, text: string, link: ?string }
            $table->json('announcement')->nullable()->after('theme_settings');

            // Header navigation menu — array of { label, url, sort_order }.
            // Themes render these in the header alongside the brand.
            $table->json('nav_menu')->nullable()->after('announcement');

            // Hero banner above the product grid on the storefront index.
            //   { enabled, title, subtitle, image_path, cta_label, cta_url }
            $table->json('hero_banner')->nullable()->after('nav_menu');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['announcement', 'nav_menu', 'hero_banner']);
        });
    }
};
