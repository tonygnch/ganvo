<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which sections of an office rack carry the desk.
 *
 * The desk is a deeper plate at desk height; the customer puts it on the
 * sections they want, and the rest carry an ordinary shelf at that level. The
 * level heights stay the same along the whole run, so the pins do not change.
 *
 * Zero-based section indexes, sorted, for an office rack; null for the other
 * models. An office rack saved before this existed is null too, and reads as
 * a desk on every section — which is what it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->json('desk_sections')->nullable()->after('desk_depth_cm');
        });
    }

    public function down(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->dropColumn('desk_sections');
        });
    }
};
