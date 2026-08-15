<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * „Подбор" → „Филтър" on Sankevi's shop filter button.
 *
 * The slot itself is new (themes/sankevi/manifest.php → shop_refine), so the
 * button is editable in Customize theme from now on. „Подбор" stays the theme's
 * default for any other yard; this is one client's wording, so it is an
 * override.
 *
 * A migration and not a seeder: deploy/deploy.sh runs `migrate --force` and
 * never `db:seed`. ganvo:sankevi-pages carries the same value for a database
 * built from scratch. down() clears the override rather than writing „Подбор"
 * back, because falling through to the default is the state it started in.
 */
return new class extends Migration
{
    private const SLOT = 'themes.sankevi.content.shop_refine';

    public function up(): void
    {
        $this->write('Филтър');
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
