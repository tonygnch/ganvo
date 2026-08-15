<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * Turn the scrolling brand tape off for Sankevi.
 *
 * The band is new-ly switchable (themes/sankevi/manifest.php → velocity_band),
 * and it defaults ON so that no other store's landing page changes. This is the
 * one store that asked for it off.
 *
 * It is a migration and not a seeder because deploy/deploy.sh runs
 * `artisan migrate --force` and never `db:seed` — a preference expressed only
 * in a seeder would never reach the server. `ganvo:sankevi-pages` also carries
 * it now, for a database built from scratch.
 *
 * Guarded on every step: a fresh database has no sankevi tenant, and a
 * migration that assumes one would break `migrate:fresh` for everybody else.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setBand(false);
    }

    public function down(): void
    {
        $this->setBand(true);
    }

    private function setBand(bool $on): void
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
        data_set($settings, 'themes.sankevi.sections.velocity_band', $on);
        $store->theme_settings = $settings;
        $store->save();
    }
};
