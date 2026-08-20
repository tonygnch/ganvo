<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * Take the heritage band off Sankevi's About page — the workshop photograph
 * with the manifesto panel laid over it.
 *
 * WRITTEN TO sections, NOT motifs, because that is where the theme declares it
 * (manifest.php, 'sections' => 'story_band'). ThemeCustomizer::on() reads
 * sections.{id} first and motifs.{id}.enabled second, so a value in the wrong
 * one appears to work — right up until the merchant saves Customize theme,
 * which rebuilds the sections array from the manifest and drops anything that
 * is not a declared section. That is exactly how hero_mark came back on once
 * before; the gutter_index and sheet_marks migrations are motifs and correctly
 * write to the other place.
 *
 * The switch is the theme's own, on by default and available in Customize
 * theme, so this only sets one store's preference. The band's copy
 * (story_h2_html, story_body) and its image slot are left untouched — turning
 * the section back on restores the page as it was.
 */
return new class extends Migration
{
    private const SLOT = 'themes.sankevi.sections.story_band';

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
