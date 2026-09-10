<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The rack configurator's price book — one row per orderable component.
 *
 * These are deliberately NOT `products`. A frame upright must be priceable by
 * the configurator while never appearing in the catalogue, and `products` has
 * no state that means that: `is_active` hides a row AND makes Cart::items()
 * prune any line referencing it, and `is_orderable` is the other way round
 * (shown, but not sellable). There is no "sellable but unlisted".
 *
 * Dimensions are nullable because they mean different things per kind:
 *   frame          height_cm + depth_cm      (e.g. 210 × 60)
 *   shelf          width_cm  + depth_cm      REAL size, e.g. 97 × 59
 *   end_pin,
 *   extension_pin,
 *   cross_brace    no dimensions at all
 *
 * Prices are EX-VAT, in the store's base currency, in minor units — matching
 * the merchant's own supplier list. VAT is applied by the calculator, not
 * stored here, because the rate is a store setting that changes independently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('sku', 60)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('depth_cm')->nullable();
            $table->unsignedSmallInteger('width_cm')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'kind']);
            // Named because the generated name would run to 58 characters.
            // NULLs compare as distinct in both SQLite and MySQL, so this does
            // not constrain the three dimensionless kinds — they are singletons
            // by definition and the seeder/admin upsert on (tenant, kind) anyway.
            $table->unique(
                ['tenant_id', 'kind', 'height_cm', 'depth_cm', 'width_cm'],
                'rack_parts_dims_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_parts');
    }
};
