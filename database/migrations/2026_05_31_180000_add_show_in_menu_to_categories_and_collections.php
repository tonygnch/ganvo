<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `show_in_menu` toggle to categories + collections so merchants can
 * pick which of them appear in the storefront's nav dropdowns. Default is
 * `true` for both new rows and existing data — that way demo stores
 * (relic, etc.) immediately benefit from the auto-populated nav without
 * a separate backfill step.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_in_menu')->default(true)->after('is_active');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('show_in_menu')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('show_in_menu');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('show_in_menu');
        });
    }
};
