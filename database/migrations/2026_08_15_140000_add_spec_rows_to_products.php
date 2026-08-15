<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product value-prop rows — the label/sub-line pairs printed under the
 * price („БЕЗПЛАТНА ДОСТАВКА / При поръчки над €50.00").
 *
 * Those were the PLATFORM'S promises, printed from lang strings on every
 * product of every store. A merchant could switch each row off, but never say
 * anything else — so a yard that delivers in three days and a florist that
 * delivers in three hours advertised the same sentence, and neither wrote it.
 *
 * Nullable, and null means "say nothing new": a product with no rows of its
 * own renders exactly what it rendered before. That is what keeps this
 * migration safe to run against live stores — it adds a capability, it does
 * not change a single existing page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('spec_rows')->nullable()->after('price_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('spec_rows');
        });
    }
};
