<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * Turn off Sankevi's gutter index — the small label stood on its end in the
 * left margin of most pages.
 *
 * It is the theme's only remaining vertical type: the shop ledger's plate
 * numbers were the other, and they went with `sheet_marks`. Turning the motif
 * off removes it everywhere at once — home, shop, about, contact, category,
 * collection, product and cart all render it behind the same `gutter_index`
 * switch — rather than leaving a rule per page for someone to miss one.
 *
 * WRITTEN TO motifs, NOT sections. ThemeCustomizer::on() checks sections.{id}
 * first and motifs.{id}.enabled second, so a value in the wrong one appears to
 * work — until the merchant saves Customize theme, which rebuilds the sections
 * array from the manifest's declared sections and drops anything else.
 *
 * The switch is the theme's own, on by default and available in Customize
 * theme, so this only sets one store's preference.
 */
return new class extends Migration
{
    private const SLOT = 'themes.sankevi.motifs.gutter_index.enabled';

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
