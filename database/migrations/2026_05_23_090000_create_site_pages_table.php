<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SuperAdmin-editable content for marketing pages (coming-soon today,
 * marketing home + others later). One row per (page, locale) pair; the
 * `content` JSON blob holds the page-specific field map.
 *
 * Generic on purpose — adding a new editable page is just inserting rows
 * with a different `page` slug, no schema migration. Each page's field
 * shape is defined in code (App\Services\SitePageSchemas), and the
 * Filament edit page renders a form from that schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            // 'coming_soon' | 'marketing_home' | future page slugs.
            $table->string('page', 64);
            $table->string('locale', 8);
            // Page-specific field map, e.g. { eyebrow: "...", headline_1: "..." }.
            // NULL or missing fields fall through to the i18n catalog default.
            $table->json('content')->nullable();
            $table->timestamps();

            $table->unique(['page', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
