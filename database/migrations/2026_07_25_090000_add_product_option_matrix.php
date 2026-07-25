<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-dimension product options.
 *
 * Until now a product's variants were a flat list of labels ("2.4 m"), which
 * cannot express a catalogue where the choices interact: a 100 cm board comes
 * in 10 and 20 cm widths, a 200 cm board in 20 and 30 — 200×10 simply does not
 * exist. Modelling that as a flat list would need one row per combination with
 * no way to know which axis is which.
 *
 * So: a product declares ordered OPTIONS (Length, Width), each with ordered
 * VALUES, and every variant is pinned to exactly one value per option. Which
 * combinations exist is then not a rule to configure — it is simply which
 * variants the merchant created. Impossible pairs are the ones with no variant.
 *
 * Additive by design: a product with no options keeps behaving exactly as it
 * does today, and existing variants keep their flat `label`, which stays the
 * display name on cart lines and orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Merchant-facing axis name — "Дължина" / "Length".
            $table->string('name', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            // The choice as the shopper sees it — "100 cm".
            $table->string('value', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_option_id', 'sort_order']);
        });

        // One row per (variant, option) — a variant is pinned to exactly one
        // value per option. The unique index is what guarantees that: without
        // it a variant could carry two Lengths and the picker's lookup would
        // become ambiguous.
        Schema::create('product_variant_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();

            $table->unique(['product_variant_id', 'product_option_id'], 'pvov_variant_option_unique');
            $table->index('product_option_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_option_values');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};
