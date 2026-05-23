<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant product categories. A merchant defines their own catalog
 * structure; categories are scoped by tenant_id (cascade-delete with
 * the tenant). Optional parent_id supports nested categories (one
 * level of nesting in the UI today, but the schema doesn't enforce
 * depth — we can deepen the tree later without a migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            // Self-referential parent for nested categories. Null = root.
            // Restrict on delete so removing a parent doesn't silently orphan
            // children — the operator has to reassign / delete kids first.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->restrictOnDelete();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Slug is unique within a tenant — different merchants can
            // both have "/categories/sale". Index by tenant + active + sort
            // for the storefront list query.
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
