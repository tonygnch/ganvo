<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-store appearance knobs for the featured-collection strips on the
     * storefront home: the banner band height and the collection-title size.
     * Stored as a small JSON blob (preset keyword + custom px) so the shape
     * can grow without further migrations. NULL = use the theme defaults
     * (standard band / medium title), which match the previously hard-coded
     * Brick values, so existing stores render identically after this ships.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('collection_display')->nullable()->after('hero_banner');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('collection_display');
        });
    }
};
