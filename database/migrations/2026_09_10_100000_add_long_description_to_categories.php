<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second description for a category: the one that runs long.
 *
 * `description` stays what it has always been — the line under the heading, on
 * the shop cover and the category page. This is the piece that could not fit
 * there: what the section is, what it is milled from, what it is used for.
 *
 * TEXT, not string. `description` is already TEXT and was never the limit; the
 * admin form was capping input at 1000 characters, which is the cut the
 * merchant kept hitting. Nothing about the column needed fixing, so nothing
 * about the column is touched here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->text('long_description')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('long_description');
        });
    }
};
