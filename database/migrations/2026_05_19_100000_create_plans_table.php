<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            // Feature bullet list shown on the plan card. JSON-cast to an
            // array on the model.
            $table->json('features')->nullable();
            // The currency the platform charges merchants in for THIS plan.
            // ISO 4217 — restricted to Money::SUPPORTED via Filament validation.
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('price_monthly_cents')->default(0);
            $table->unsignedInteger('price_yearly_cents')->default(0);
            // Render a "Most popular" badge on this card in the wizard.
            $table->boolean('is_popular')->default(false);
            // When false, the plan is hidden from the wizard but stays in DB
            // so existing tenant.subscription_plan slugs still resolve.
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // Optional time-bounded promo discount, applied to both billing
            // periods. Renders strikethrough + reduced price in the wizard.
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->string('discount_label')->nullable();
            $table->timestamp('discount_starts_at')->nullable();
            $table->timestamp('discount_ends_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
