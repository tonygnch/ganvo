<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sankevi's starting price book, and the switch that turns the configurator on
 * for them alone.
 *
 * IMPORTANT: these figures are transcribed from Stellingspecialist's public
 * Easyrack list (the client's brief, §16-18) purely so the thing runs on real
 * numbers instead of zeroes. They are a COMPETITOR'S prices and Sankevi must
 * replace them from Storefront → Rack configurator before this goes live. The
 * whole point of the price book being editable is that nothing here is final.
 *
 * The VAT default is 2000 bp — Bulgaria's 20%, not the 21% in the brief, which
 * is the Dutch source site's rate.
 *
 * Idempotent: skips a tenant that already has parts, so a re-run cannot
 * overwrite prices the merchant has since corrected.
 */
return new class extends Migration
{
    /** [height => [depth => euros]] */
    private const FRAMES = [
        150 => [30 => 20.45, 40 => 20.84, 50 => 22.07, 60 => 22.91],
        180 => [30 => 20.42, 40 => 21.00, 50 => 21.95, 60 => 22.68],
        210 => [30 => 20.63, 40 => 21.46, 50 => 22.94, 60 => 23.55],
        240 => [30 => 23.51, 40 => 24.12, 50 => 25.40, 60 => 29.45],
        300 => [30 => 36.05, 40 => 37.28, 50 => 38.42, 60 => 42.55],
    ];

    /** [real width => [real depth => euros]] */
    private const SHELVES = [
        77 => [29 => 11.90, 39 => 15.83, 49 => 19.46, 59 => 22.26],
        97 => [29 => 13.32, 39 => 16.59, 49 => 20.32, 59 => 23.70],
        117 => [29 => 16.37, 39 => 20.90, 49 => 25.22, 59 => 30.16],
    ];

    /** [kind => [sku, euros]] */
    private const FLAT = [
        'end_pin' => ['END-PIN-10', 0.38],
        'extension_pin' => ['EXT-PIN-10', 0.36],
        'cross_brace' => ['CROSS-BRACE', 7.10],
    ];

    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'sankevi')->value('id');
        if (! $tenantId) {
            return;
        }

        $store = DB::table('stores')->where('tenant_id', $tenantId)->first();
        if (! $store) {
            return;
        }

        $settings = json_decode((string) ($store->rack_configurator ?? ''), true) ?: [];
        $settings['enabled'] = true;
        DB::table('stores')->where('id', $store->id)->update([
            'rack_configurator' => json_encode($settings),
            'updated_at' => now(),
        ]);

        if (DB::table('rack_parts')->where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $now = now();
        $rows = [];
        $sort = 0;

        foreach (self::FRAMES as $height => $byDepth) {
            foreach ($byDepth as $depth => $euros) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'kind' => 'frame',
                    'sku' => sprintf('FRAME-%d-%d', $height, $depth),
                    'height_cm' => $height,
                    'depth_cm' => $depth,
                    'width_cm' => null,
                    'price_cents' => (int) round($euros * 100),
                    'is_active' => true,
                    'sort_order' => $sort++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (self::SHELVES as $width => $byDepth) {
            foreach ($byDepth as $depth => $euros) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'kind' => 'shelf',
                    'sku' => sprintf('SHELF-%d-%d', $width, $depth),
                    'height_cm' => null,
                    'depth_cm' => $depth,
                    'width_cm' => $width,
                    'price_cents' => (int) round($euros * 100),
                    'is_active' => true,
                    'sort_order' => $sort++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (self::FLAT as $kind => [$sku, $euros]) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'kind' => $kind,
                'sku' => $sku,
                'height_cm' => null,
                'depth_cm' => null,
                'width_cm' => null,
                'price_cents' => (int) round($euros * 100),
                'is_active' => true,
                'sort_order' => $sort++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('rack_parts')->insert($rows);
    }

    public function down(): void
    {
        $tenantId = DB::table('tenants')->where('slug', 'sankevi')->value('id');
        if (! $tenantId) {
            return;
        }

        DB::table('rack_parts')->where('tenant_id', $tenantId)->delete();

        $store = DB::table('stores')->where('tenant_id', $tenantId)->first();
        if ($store) {
            $settings = json_decode((string) ($store->rack_configurator ?? ''), true) ?: [];
            unset($settings['enabled']);
            DB::table('stores')->where('id', $store->id)->update([
                'rack_configurator' => $settings ? json_encode($settings) : null,
            ]);
        }
    }
};
