<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A rack somebody built, saved by a short public code so it can be reopened
 * at /configurator/{code} and shared by link.
 *
 * `segments` is the list of NOMINAL section widths in order — [100,100,80,120]
 * — which is the whole configuration once height/depth/levels are fixed. The
 * real shelf sizes are derived (100 → 97), never stored, so a change to the
 * size mapping does not strand old configurations.
 *
 * The price columns are PROVENANCE, not truth. The cart and the checkout
 * always recompute from live rack_parts, because a saved link that quietly
 * kept last month's price would be a promise the yard never made. What is
 * frozen for an order is frozen on the ORDER (see order_rack_items).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12)->unique();
            $table->unsignedSmallInteger('height_cm');
            $table->unsignedSmallInteger('depth_cm');
            $table->unsignedSmallInteger('levels');
            $table->json('segments');
            $table->json('bom')->nullable();
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('vat_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            // Basis points, so 20% is 2000 and a 19.5% rate stays exact.
            $table->unsignedSmallInteger('vat_rate_bp')->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_configurations');
    }
};
