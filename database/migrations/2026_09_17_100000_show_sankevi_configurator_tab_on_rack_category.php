<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The side „Конфигуратор“ tab used to show on every page. It now shows only
 * on the category pages the merchant picks (stores.rack_configurator
 * .tab_category_ids), and Sankevi asked for it on „Модулни и стелажни системи“.
 *
 * Found by slug, or by name if the slug differs. If the category is not there,
 * or the merchant has already chosen, nothing is written — the tab can be
 * placed from the admin (Конфигуратор на стелажи → Настройки).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tenant = DB::table('tenants')->where('slug', 'sankevi')->first();
        if (! $tenant) {
            return;
        }

        $category = DB::table('categories')
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('slug', 'modulni-i-stelazni-sistemi')->orWhere('name', 'Модулни и стелажни системи'))
            ->orderByRaw("slug = 'modulni-i-stelazni-sistemi' desc")
            ->first();
        $store = DB::table('stores')->where('tenant_id', $tenant->id)->first();
        if (! $category || ! $store) {
            return;
        }

        $settings = json_decode((string) $store->rack_configurator, true) ?: [];
        if (array_key_exists('tab_category_ids', $settings)) {
            return;
        }

        $settings['tab_category_ids'] = [(int) $category->id];
        DB::table('stores')->where('id', $store->id)->update(['rack_configurator' => json_encode($settings, JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        // Leaves the choice in place: without the code that reads it, it is inert.
    }
};
