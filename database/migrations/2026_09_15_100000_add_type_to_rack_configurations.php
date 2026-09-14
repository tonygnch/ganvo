<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which rack model a saved configuration is.
 *
 *   single   the plain shelving rack — frames, flat shelves, pins, braces
 *   office   the same, with one level a desk top at working height
 *   wine     rimmed trays in place of the flat shelves
 *
 * The model decides which boards the calculator prices, so it is part of what
 * a share code names: the same frame sizes as an office rack are a different
 * rack at a different price.
 *
 * Every rack saved before models existed was the plain one, which is what the
 * default says.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->string('type', 12)->default('single')->after('levels');
        });
    }

    public function down(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
