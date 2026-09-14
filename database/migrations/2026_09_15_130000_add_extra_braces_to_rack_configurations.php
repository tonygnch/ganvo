<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-braces the customer added by hand.
 *
 * Every other section carries a brace as standard (§4) and always will. The
 * customer may add one to any other section, and those choices are part of
 * the rack: they change the parts list and the price, and a share link has to
 * reopen with them.
 *
 * Zero-based section indexes, sorted, only the sections that would not carry
 * a brace anyway. Null for every rack saved before this existed — which is the
 * same as none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->json('extra_braces')->nullable()->after('segments');
        });
    }

    public function down(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->dropColumn('extra_braces');
        });
    }
};
