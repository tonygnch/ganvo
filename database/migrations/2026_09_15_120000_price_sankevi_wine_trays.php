<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sankevi's wine tray prices: each tray costs the same-size shelf plus 5 €.
 *
 * The rule Tony set on 2026-09-15. Like the whole price book the figure is
 * EX-VAT, so a customer sees 6 € more per tray at 20% VAT. One row per shelf
 * size, active when that shelf is.
 *
 * Only sizes without a tray row are filled, so re-running it — or running it
 * after Sankevi have typed their own tray prices in the admin — never
 * overwrites a price. And the shelf prices it copies are still the placeholder
 * figures from 2026_09_10_150000_seed_sankevi_rack_parts: when Sankevi enter
 * their real shelf prices, the trays do NOT follow on their own. They are
 * separate rows, edited in the „Табли за вино" tab.
 */
return new class extends Migration
{
    private const TRAY_SURCHARGE_CENTS = 500;

    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'sankevi')->value('id');
        if (! $tenantId) {
            return;
        }

        $shelves = DB::table('rack_parts')
            ->where('tenant_id', $tenantId)
            ->where('kind', 'shelf')
            ->get();

        $now = now();
        foreach ($shelves as $shelf) {
            $exists = DB::table('rack_parts')
                ->where('tenant_id', $tenantId)
                ->where('kind', 'wine_tray')
                ->where('width_cm', $shelf->width_cm)
                ->where('depth_cm', $shelf->depth_cm)
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('rack_parts')->insert([
                'tenant_id' => $tenantId,
                'kind' => 'wine_tray',
                'sku' => sprintf('WINE-TRAY-%d-%d', $shelf->width_cm, $shelf->depth_cm),
                'height_cm' => null,
                'width_cm' => $shelf->width_cm,
                'depth_cm' => $shelf->depth_cm,
                'price_cents' => (int) $shelf->price_cents + self::TRAY_SURCHARGE_CENTS,
                'is_active' => $shelf->is_active,
                'sort_order' => $shelf->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Deliberately nothing: by the time anyone rolls back, these may be
        // prices Sankevi have edited, and a rollback must not delete them.
    }
};
