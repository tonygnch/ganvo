<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sankevi stops pretending to take money.
 *
 * The store was on order_flow = 'payment' with no Stripe Connect account, which
 * means checkout fell through to the legacy stub path — and that path stamps an
 * order `paid` with a `paid_at`, having taken nothing. For a yard selling
 * cut-to-spec timber and made-to-measure racks running into thousands of euros,
 * that is the worst of both worlds: no money, and a record saying otherwise.
 *
 * Enquiry is what this business actually does. The whole Sankevi theme is
 * already written for it — cart, checkout and order views all branch on
 * isEnquiryFlow() into "cutting list", "estimate", "nobody is being charged"
 * wording that has been dead code until now.
 *
 * The shipping methods have to go with it. The store never set any, so
 * Store::shippingMethods() was handing the checkout the PLATFORM defaults:
 * "Standard shipping €5, free over €50" and "Express shipping €15" — in
 * English, on a Bulgarian storefront, adding a real charge to a figure the page
 * calls an estimate. A yard that quotes haulage by the lorry-load replaces them
 * with the truth: delivery is agreed when the order is.
 *
 * Existing orders keep their own snapshotted order_flow, so nothing in the
 * history is rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'sankevi')->value('id');
        if (! $tenantId) {
            return;
        }

        DB::table('stores')->where('tenant_id', $tenantId)->update([
            'order_flow' => 'enquiry',
            'shipping_methods' => json_encode([[
                'id' => 'agreed',
                'label' => 'Доставка по договаряне',
                'description' => 'Уточняваме транспорта заедно с офертата.',
                'price_cents' => 0,
                'free_threshold_cents' => null,
            ]], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'sankevi')->value('id');
        if (! $tenantId) {
            return;
        }

        DB::table('stores')->where('tenant_id', $tenantId)->update([
            'order_flow' => 'payment',
            'shipping_methods' => null,
        ]);
    }
};
