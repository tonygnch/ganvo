<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            // Optional banner shown on the collection's own page + as
            // a backdrop on the homepage strip when set.
            $table->string('banner_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            // is_featured = surface this strip on the storefront homepage.
            // is_active   = is this collection visible at all (its own
            //               /collections/{slug} page included).
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Slug unique per tenant — collections live in tenant namespace.
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_featured', 'sort_order']);
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Operator-controlled product order within the collection
            // — first product shows first on the storefront strip.
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'product_id']);
            $table->index(['collection_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
    }
};
