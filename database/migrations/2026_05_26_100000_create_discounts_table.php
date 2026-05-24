<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // Human-readable label shown in admin + on order line items.
            $table->string('name');
            // Customer-entered code. Nullable for auto-discounts (which
            // apply silently when conditions match — no code needed).
            $table->string('code')->nullable();
            // 'percentage' = value is 0–100 percent of subtotal
            // 'fixed'      = value is cents to subtract from subtotal
            // 'free_shipping' = subtract the shipping line (value unused)
            $table->string('type'); // percentage | fixed | free_shipping
            $table->unsignedBigInteger('value')->default(0);
            // Minimum cart subtotal (cents) to qualify. Null = no minimum.
            $table->unsignedBigInteger('min_subtotal_cents')->nullable();
            // Validity window. Null = open-ended in that direction.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            // Cap how often this can ever be used (across all customers).
            // Null = no cap.
            $table->unsignedInteger('usage_limit')->nullable();
            // Cap per individual customer. Null = no cap. (Enforced
            // only for authenticated checkouts; guest checkouts ignore
            // it — we don't have a reliable identity.)
            $table->unsignedInteger('per_customer_limit')->nullable();
            // Denormalized counter; we increment on order placement.
            $table->unsignedInteger('times_used')->default(0);
            // Auto-discounts skip the code input — engine applies the
            // best matching one automatically.
            $table->boolean('is_auto')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Codes are case-insensitive per tenant — operator typing
            // SUMMER10 in admin, customer typing summer10 at checkout
            // should match. We store as uppercase + look up uppercase.
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active', 'is_auto']);
        });

        // Order columns to snapshot the discount that was applied so the
        // receipt + admin order screens stay accurate even after the
        // discount is renamed, deactivated, or deleted.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('discount_id')->nullable()->after('display_total_cents')->nullOnDelete();
            $table->string('discount_code')->nullable()->after('discount_id');
            $table->string('discount_name')->nullable()->after('discount_code');
            $table->unsignedBigInteger('discount_amount_cents')->default(0)->after('discount_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_id');
            $table->dropColumn(['discount_code', 'discount_name', 'discount_amount_cents']);
        });
        Schema::dropIfExists('discounts');
    }
};
