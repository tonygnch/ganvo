<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Switch the platform default currency from USD to EUR. Updates every row
 * that was created under the old USD default so existing dev/demo data
 * doesn't keep showing dollar prices after this change.
 *
 * Column defaults are flipped separately in the original `create_*` and
 * `add_currency_fields_*` migrations — those defaults only affect newly
 * inserted rows; this data migration is for what's already in the database.
 *
 * Stores that have already been deliberately set to USD by a real merchant
 * (post-onboarding) would be flipped too — that's a tradeoff of doing this
 * as a blunt UPDATE. For dev/demo data this is fine; in production this
 * migration should be removed or scoped before running.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Each table is updated independently; failures on one don't roll the
        // others back, but they're idempotent — re-running is safe.
        DB::table('stores')->where('currency', 'USD')->update(['currency' => 'EUR']);
        DB::table('products')->where('currency', 'USD')->update(['currency' => 'EUR']);
        DB::table('orders')->where('currency', 'USD')->update(['currency' => 'EUR']);
        DB::table('plans')->where('currency', 'USD')->update(['currency' => 'EUR']);

        // Stores with display_currencies arrays that lead with USD get
        // reshuffled so EUR is first (the storefront defaults to the first
        // entry as the base display).
        $stores = DB::table('stores')->whereNotNull('display_currencies')->get(['id', 'display_currencies']);
        foreach ($stores as $store) {
            $list = json_decode($store->display_currencies, true) ?: [];
            if (! is_array($list) || empty($list)) {
                continue;
            }
            $list = array_values(array_map(fn ($c) => strtoupper((string) $c), $list));
            // Only touch lists that contain USD — leave others alone.
            if (! in_array('USD', $list, true)) {
                continue;
            }
            // Ensure EUR is present and pulled to the front.
            $list = array_values(array_diff($list, ['EUR']));
            array_unshift($list, 'EUR');
            DB::table('stores')->where('id', $store->id)->update([
                'display_currencies' => json_encode(array_values(array_unique($list))),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally a no-op. Rolling back would re-introduce USD pricing
        // for the same rows, which is destructive in a different direction.
    }
};
