<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * Strip Sankevi's About page back to its story and its photographs.
 *
 * Off go the title block, the milestones timeline, the figures band, the
 * heading above the photographs, and the "get in touch" button at the foot —
 * five of the theme's own section switches, all on by default, so no other
 * store on this theme is touched.
 *
 * NOTHING IS DELETED. Every one of these blocks draws on the merchant's own
 * content: the milestones they typed, the figures they counted, the heading
 * copy. Removing the sections from the template would have meant clearing that
 * content to hide it, and a merchant who changed their mind would have had
 * nothing to switch back on. These switches hide the blocks and keep the words.
 *
 * WRITTEN TO sections, NOT motifs, because that is where the theme declares
 * them. ThemeCustomizer::on() reads sections.{id} first and motifs.{id}.enabled
 * second, so a value in the wrong one works until the merchant saves Customize
 * theme — which rebuilds the sections array from the manifest and drops
 * anything that is not a declared section. That is how hero_mark came back on
 * once before.
 *
 * ABOUT THE HEADING: with about_head off the page has no <h1>. That was the
 * owner's explicit instruction, and it is recorded here because it is the kind
 * of thing that looks like a bug to whoever reads this next — it is not.
 */
return new class extends Migration
{
    private const SLOTS = [
        'themes.sankevi.sections.about_head',
        'themes.sankevi.sections.about_milestones',
        'themes.sankevi.sections.about_stats',
        'themes.sankevi.sections.about_gallery_head',
        'themes.sankevi.sections.about_cta',
    ];

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
        foreach (self::SLOTS as $slot) {
            data_set($settings, $slot, $on);
        }
        $store->theme_settings = $settings;
        $store->save();
    }
};
