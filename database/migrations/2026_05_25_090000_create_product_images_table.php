<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra product images beyond the primary one. The existing
 * products.image_path column stays as the primary (used everywhere:
 * cart thumbs, order summary, product cards). Rows here are
 * gallery extras shown on the product detail page.
 *
 * Kept separate from products.image_path on purpose: cart + order
 * + table-thumb code doesn't have to know about multi-image; it
 * keeps reading image_path. The product page is the only place
 * that pulls the whole gallery together (primary + these).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Listed N times per product page render; cheap index.
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
