<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Human-readable label shown in the picker + on order lines
            // (e.g. "Small / Red", "Pro", "256GB · Silver").
            $table->string('label');
            $table->string('sku')->nullable();
            // Per-variant price override in cents; null means "use the
            // parent product's price_cents". Keeps the common case of
            // size-only variants from having to repeat the price.
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        // OrderItems remember which variant was purchased AND a snapshot
        // of its label — variants can be renamed or deleted; the receipt
        // must still read accurately months later.
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->nullOnDelete();
            $table->string('variant_label')->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn('variant_label');
        });

        Schema::dropIfExists('product_variants');
    }
};
