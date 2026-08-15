<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * „Целият склад" → „Всички продукти" on Sankevi's family wheel.
 *
 * An OVERRIDE, not an edit to site.storefront.sankevi.families_all: that string
 * is the theme's default and belongs to any other yard that picks this theme.
 * One client's preferred wording is not a platform default.
 *
 * A migration and not a seeder because deploy/deploy.sh runs `migrate --force`
 * and never `db:seed`. ganvo:sankevi-pages carries the same value for a
 * database built from scratch.
 *
 * down() clears the override rather than writing the old text back, so the slot
 * returns to falling through to the lang default — which is what "no override"
 * actually means here.
 */
return new class extends Migration
{
    private const SLOT = 'themes.sankevi.content.families_all';

    public function up(): void
    {
        $this->write('Всички продукти');
    }

    public function down(): void
    {
        $this->write('');
    }

    private function write(string $value): void
    {
        $tenant = Tenant::where('slug', 'sankevi')->first();
        if (! $tenant) {
            return;
        }

        $store = Store::where('tenant_id', $tenant->id)->first();
        if (! $store) {
            return;
        }

        $settings = (array) $store->theme_settings;
        data_set($settings, self::SLOT, $value);
        $store->theme_settings = $settings;
        $store->save();
    }
};
