<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront history / about page.
 *
 * One more merchant-authored JSON blob on stores, alongside announcement /
 * nav_menu / hero_banner / contact. Everything the page renders — story,
 * milestones, stats, images — lives in here, so a merchant who never opens
 * the section costs us nothing but a NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('about')->nullable()->after('contact');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('about');
        });
    }
};
