<?php

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * The About page introduces itself as „За нас" rather than „Работилницата".
 *
 * The label is the theme's `story_eyebrow` copy slot, which the page prints
 * above its heading. Sentence case, because the style sheet uppercases it —
 * stored as „ЗА НАС" it would still read ЗА НАС on the page and shout in the
 * admin field the merchant edits it in. It matches the nav's own wording for
 * the same page (`site.storefront.nav.about`).
 *
 * WRITTEN TO content, which is where ThemeCustomizer::copy() looks for a
 * merchant's override before falling back to the theme's default string. A
 * per-store preference, so it belongs in the store rather than in lang/, where
 * it would rename the slot for every shop that ever picks this theme.
 */
return new class extends Migration
{
    private const SLOT = 'themes.sankevi.content.story_eyebrow';

    public function up(): void
    {
        // Sentence case: `.crumb` and the gutter index both uppercase in CSS.
        $this->set('За нас');
    }

    public function down(): void
    {
        // Back to the theme default, which the fallback supplies on its own.
        $this->set(null);
    }

    private function set(?string $value): void
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

        if ($value === null) {
            data_forget($settings, self::SLOT);
        } else {
            data_set($settings, self::SLOT, $value);
        }

        $store->theme_settings = $settings;
        $store->save();
    }
};
