<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * Turn off the „№ 01" plate numbers on Sankevi's product cards and cart lines.
 *
 * WRITTEN TO motifs, NOT sections. ThemeCustomizer::on() checks sections.{id}
 * first and motifs.{id}.enabled second, so a value in the wrong one appears to
 * work — until the merchant saves Customize theme, which rebuilds the sections
 * array from the manifest's declared sections and drops anything else. That is
 * exactly how hero_mark silently came back on once before.
 *
 * The switch itself is the theme's own (`sheet_marks`), on by default and
 * available in Customize theme, so this only sets one store's preference.
 */
return new class extends Migration
{
    private const SLOT = 'themes.sankevi.motifs.sheet_marks.enabled';

    public function up(): void
    {
        $this->set(false);
    }

    public function down(): void
    {
        $this->set(true);
    }

    private function set(bool $on): void
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
        data_set($settings, self::SLOT, $on);
        $store->theme_settings = $settings;
        $store->save();
    }
};
