<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Demo stores for the four new themes — Ember (coffee), Kiln (ceramics),
 * Sediment (wine), Forma (single product). Each gets a tenant + store on its
 * theme with a small, on-topic catalogue so the theme can be shown with real
 * content. Products use NO images on purpose — the themes render their own
 * gradient/CSS placeholders, which is exactly what the design mockups use and
 * avoids unreliable stock-photo sourcing.
 *
 *   php artisan db:seed --class=Database\\Seeders\\NewThemesDemoSeeder
 *
 * Idempotent: re-running wipes and re-seeds each catalogue.
 * Owner login per store:    owner@<slug>.test / password
 * Customer login per store: shopper@<slug>.test / password
 */
class NewThemesDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->stores() as $slug => $cfg) {
            $this->seedStore($slug, $cfg);
        }
    }

    private function seedStore(string $slug, array $cfg): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $cfg['name'],
                'business_type' => 'retail',
                'contact_email' => "hello@{$slug}.test",
                'subscription_plan' => 'starter',
                'status' => 'active',
                'onboarded_at' => now(),
            ]
        );

        $store = Store::firstOrCreate(['tenant_id' => $tenant->id], ['currency' => $cfg['currency']]);

        $owner = User::firstOrCreate(
            ['email' => "owner@{$slug}.test"],
            [
                'tenant_id' => $tenant->id,
                'name' => $cfg['name'] . ' Studio',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (($role = Role::where('name', 'store_admin')->first()) && ! $owner->hasRole($role)) {
            $owner->assignRole($role);
        }
        Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => "shopper@{$slug}.test"],
            ['name' => 'Demo Shopper', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $this->command->info("Seeding {$cfg['name']} ({$slug})…");
        $this->wipe($tenant);

        // Categories
        $cats = [];
        $i = 0;
        foreach ($cfg['cats'] as $cslug => [$name, $desc]) {
            $cats[$cslug] = Category::create([
                'tenant_id' => $tenant->id, 'name' => $name, 'slug' => $cslug,
                'description' => $desc, 'image_path' => null, 'sort_order' => $i++,
                'is_active' => true, 'show_in_menu' => true,
            ]);
        }

        // Products
        $products = [];
        foreach ($cfg['products'] as $p) {
            [$catSlug, $name, $desc, $price, $variants] = $p;
            $pslug = Str::slug($name);
            $product = Product::create([
                'tenant_id' => $tenant->id, 'name' => $name, 'slug' => $pslug,
                'description' => $desc, 'price_cents' => $price, 'currency' => $cfg['currency'],
                'stock_quantity' => 24, 'image_path' => null, 'is_active' => true,
            ]);
            if (isset($cats[$catSlug])) {
                DB::table('category_product')->insert(['category_id' => $cats[$catSlug]->id, 'product_id' => $product->id]);
            }
            if ($variants && ! empty($cfg['sizes'])) {
                foreach ($cfg['sizes'] as $vi => [$label, $mult, $stock]) {
                    ProductVariant::create([
                        'product_id' => $product->id, 'label' => $label,
                        'sku' => $pslug . '-' . Str::slug($label),
                        'price_cents' => (int) round($price * $mult / 50) * 50,
                        'stock_quantity' => $stock, 'sort_order' => $vi, 'is_active' => true,
                    ]);
                }
            }
            $product->_cat = $catSlug;
            $products[] = $product;
        }

        // One featured collection
        if (! empty($cfg['collection'])) {
            [$cslug, $title, $desc] = $cfg['collection'];
            $coll = Collection::create([
                'tenant_id' => $tenant->id, 'title' => $title, 'slug' => $cslug,
                'description' => $desc, 'banner_path' => null, 'sort_order' => 0,
                'is_featured' => true, 'is_active' => true, 'show_in_menu' => true,
            ]);
            $sort = 0;
            foreach (array_slice($products, 0, 6) as $p) {
                DB::table('collection_product')->insert([
                    'collection_id' => $coll->id, 'product_id' => $p->id,
                    'sort_order' => $sort++, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $this->configureStore($store, $slug, $cfg);
        $this->command->info('  → http://' . $slug . '.' . config('ganvo.central_domain') . ':8000  (' . count($products) . ' products)');
    }

    private function configureStore(Store $store, string $slug, array $cfg): void
    {
        $nav = [['label' => 'Shop', 'url' => '/', 'sort_order' => 0, 'auto_source' => null, 'children' => []]];
        $so = 1;
        foreach ($cfg['cats'] as $cslug => $c) {
            if ($c[0] === 'Shop') {
                continue; // Forma's lone category is literally "Shop" — skip the duplicate nav entry
            }
            $nav[] = ['label' => $c[0], 'url' => "/categories/{$cslug}", 'sort_order' => $so++, 'auto_source' => null, 'children' => []];
        }

        $store->update([
            'theme' => $slug,
            'primary_color' => $cfg['accent'],
            'is_live' => true,
            'currency' => $cfg['currency'],
            'display_currencies' => ['EUR', 'USD', 'GBP', 'BGN'],
            'fx_rates' => ['USD' => 1.09, 'GBP' => 0.86],
            'announcement' => ['enabled' => true, 'text' => $cfg['announce'], 'link' => null, 'speed' => 'normal'],
            'nav_menu' => $nav,
            'hero_banner' => [
                'enabled' => true,
                'title' => $cfg['hero'][0],
                'subtitle' => $cfg['hero'][1],
                'image_path' => null,
                'cta_label' => $cfg['hero'][2],
                'cta_url' => '#shop',
            ],
        ]);
    }

    private function wipe(Tenant $tenant): void
    {
        $ids = Product::where('tenant_id', $tenant->id)->withTrashed()->pluck('id');
        ProductVariant::whereIn('product_id', $ids)->delete();
        DB::table('collection_product')->whereIn('product_id', $ids)->delete();
        DB::table('category_product')->whereIn('product_id', $ids)->delete();
        Product::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
        Collection::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
        Category::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
    }

    /** @return array<string, array> */
    private function stores(): array
    {
        return [
            'ember' => [
                'name' => 'Ember', 'accent' => '#b0542a', 'currency' => 'EUR',
                'announce' => 'Roasted to order · Free shipping over €40 · Beans shipped within 48h',
                'hero' => ['Roasted to order', 'Coffee, fresh from the drum.', 'Shop coffee'],
                'sizes' => [['250g', 0.6, 20], ['500g', 1.0, 12], ['1kg', 1.8, 6]],
                'cats' => [
                    'single-origin' => ['Single Origin', 'Traceable lots from a single farm or co-op.'],
                    'blends' => ['Blends', 'House blends built for everyday brewing.'],
                    'equipment' => ['Equipment', 'Tools for a better cup at home.'],
                ],
                'products' => [
                    ['single-origin', 'Ethiopia Yirgacheffe', 'Floral and bright — jasmine, bergamot and stone fruit.', 1850, true],
                    ['single-origin', 'Colombia Huila', 'Round and sweet — red apple, caramel and cocoa.', 1750, true],
                    ['single-origin', 'Guatemala Antigua', 'Classic and balanced — chocolate, almond and orange.', 1700, false],
                    ['blends', 'House Espresso', 'Our everyday shot — dark cherry, brown sugar and walnut.', 1600, true],
                    ['blends', 'Breakfast Blend', 'Smooth and comforting — toast, hazelnut and milk chocolate.', 1450, false],
                    ['equipment', 'Ceramic Dripper', 'A single-cup pour-over cone in matte stoneware.', 3200, false],
                    ['equipment', 'Gooseneck Kettle', 'Precise pour control for even extraction.', 6800, false],
                ],
                'collection' => ['this-month', "This month's roasts", 'Fresh arrivals on the menu right now.'],
            ],
            'kiln' => [
                'name' => 'Kiln', 'accent' => '#a9774a', 'currency' => 'EUR',
                'announce' => 'Hand-thrown in small batches · Each piece is one of a kind',
                'hero' => ['Hand-thrown stoneware', 'Made by hand, made to use.', 'Shop the studio'],
                'sizes' => [['Single', 1.0, 14], ['Set of 2', 1.9, 8], ['Set of 4', 3.6, 4]],
                'cats' => [
                    'tableware' => ['Tableware', 'Mugs, plates and bowls for everyday meals.'],
                    'vases' => ['Vases', 'Vessels for stems, branches and dried stems.'],
                    'decor' => ['Decor', 'Small objects to live with.'],
                ],
                'products' => [
                    ['tableware', 'Stoneware Mug', 'A generous, comfortable mug with a speckled glaze.', 2800, true],
                    ['tableware', 'Dinner Plate', 'A wide, sturdy plate that stacks and lasts.', 3400, true],
                    ['tableware', 'Pasta Bowl', 'A low, broad bowl for pasta, grains and salads.', 3600, false],
                    ['vases', 'Bud Vase', 'A small vessel for a single stem or two.', 3000, false],
                    ['vases', 'Tall Vessel', 'A sculptural vase for branches and dried stems.', 5800, false],
                    ['decor', 'Incense Holder', 'A weighty little dish with a hand-pierced hole.', 1900, false],
                    ['decor', 'Trinket Dish', 'A catch-all for rings, keys and small things.', 1600, false],
                ],
                'collection' => ['new-from-the-kiln', 'New from the kiln', 'The latest pieces, just out of the firing.'],
            ],
            'wick' => [
                'name' => 'Wick', 'accent' => '#d99a4e', 'currency' => 'EUR',
                'announce' => 'Small-batch soy wax · Poured by hand · Cotton wicks, clean burn',
                'hero' => ['Candlelit apothecary', 'Lit, not loud.', 'Shop the bench'],
                'sizes' => [['Votive', 0.5, 22], ['Classic', 1.0, 14], ['Three-wick', 1.9, 5]],
                'cats' => [
                    'candles' => ['Candles', 'Hand-poured soy candles in amber glass.'],
                    'home-fragrance' => ['Home Fragrance', 'Diffusers, room sprays and incense.'],
                    'accessories' => ['Accessories', 'Trimmers, snuffers and long matches.'],
                ],
                'products' => [
                    ['candles', 'Hearth', 'Cedar, woodsmoke and a whisper of clove — a fire you can put on a shelf.', 3400, true],
                    ['candles', 'Library', 'Old paper, worn leather and pipe tobacco — quiet hours in wax.', 3400, true],
                    ['candles', 'Orchard Dusk', 'Ripe fig, fig leaf and warm honey — late summer, ending slowly.', 3200, true],
                    ['candles', 'Black Honey', 'Dark amber, beeswax and smoked vanilla — sweetness with a shadow.', 3600, false],
                    ['home-fragrance', 'Chapel Incense', 'Frankincense, myrrh and cold stone — hand-rolled, slow-burning.', 1800, false],
                    ['home-fragrance', 'Sea Fog Room Spray', 'Salt air, driftwood and white musk in a single pull.', 2200, false],
                    ['accessories', 'Brass Wick Trimmer', 'A weighty brass trimmer for a clean, even burn.', 1600, false],
                ],
                'collection' => ['on-the-bench', 'On the bench', 'What we are pouring this month.'],
            ],
            'forma' => [
                'name' => 'Forma', 'accent' => '#2f4fe0', 'currency' => 'EUR',
                'announce' => 'One product, done properly · Free shipping worldwide · 30-day returns',
                'hero' => ['Meet Cobalt', 'One bottle. Endlessly considered.', 'Configure yours'],
                'sizes' => [['500 ml', 1.0, 40], ['750 ml', 1.25, 22]],
                'cats' => [
                    'shop' => ['Shop', 'The bottle and everything for it.'],
                ],
                'products' => [
                    ['shop', 'Cobalt', 'A vacuum-insulated bottle, engineered to a single, perfect form. Keeps cold 24h, hot 12h.', 4500, true],
                    ['shop', 'Cobalt Cap — Sport', 'A one-handed flip cap for the move.', 1200, false],
                    ['shop', 'Cobalt Brush Kit', 'A long brush and bottle-safe tablets to keep it fresh.', 900, false],
                ],
                'collection' => ['accessories', 'Accessories', 'Everything that goes with Cobalt.'],
            ],
        ];
    }
}
