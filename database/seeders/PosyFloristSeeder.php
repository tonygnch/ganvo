<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Posy — a seasonal-florist demo store for the Posy theme.
 *
 *   php artisan db:seed --class=Database\\Seeders\\PosyFloristSeeder
 *
 * Creates (or refreshes) the `posy` tenant with a florist catalogue —
 * bouquets, plants, dried stems and vases — on the Posy theme, so the
 * theme can be shown with content that actually matches it. Idempotent:
 * re-running wipes and re-seeds the catalogue.
 *
 * Owner login:    owner@posy.test / password   (/store admin)
 * Customer login: elsie@posy.test / password   (storefront account)
 * Storefront:     http://posy.<central-domain>:8000
 */
class PosyFloristSeeder extends Seeder
{
    private const SLUG = 'posy';

    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Posy',
                'business_type' => 'retail',
                'contact_email' => 'hello@posy.test',
                'subscription_plan' => 'starter',
                'status' => 'active',
                'onboarded_at' => now(),
            ]
        );

        $store = Store::firstOrCreate(['tenant_id' => $tenant->id], ['currency' => 'EUR']);

        // Owner (store admin) + a demo customer for the account pages.
        $owner = User::firstOrCreate(
            ['email' => 'owner@posy.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Posy Studio',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if ($role = Role::where('name', 'store_admin')->first()) {
            if (! $owner->hasRole($role)) {
                $owner->assignRole($role);
            }
        }
        Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'elsie@posy.test'],
            [
                'name' => 'Elsie Booth',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Seeding Posy florist for tenant '" . self::SLUG . "' (id={$tenant->id})…");

        $this->wipeExisting($tenant);
        Storage::disk('public')->makeDirectory('demo/posy');

        $categories = $this->seedCategories($tenant);
        $products   = $this->seedProducts($tenant, $categories);
        $this->seedVariants($products);
        $this->seedCollections($tenant, $products);
        $this->configureStore($store);

        $this->command->info('Done — ' . count($categories) . ' categories, ' . count($products) . ' products.');
        $this->command->info('  Storefront: http://' . self::SLUG . '.' . config('ganvo.central_domain') . ':8000');
    }

    private function wipeExisting(Tenant $tenant): void
    {
        $productIds = Product::where('tenant_id', $tenant->id)->withTrashed()->pluck('id');
        ProductImage::whereIn('product_id', $productIds)->delete();
        ProductVariant::whereIn('product_id', $productIds)->delete();
        DB::table('collection_product')->whereIn('product_id', $productIds)->delete();
        DB::table('category_product')->whereIn('product_id', $productIds)->delete();
        Product::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
        Collection::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
        Category::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
    }

    /** @return array<string, Category> slug => Category */
    private function seedCategories(Tenant $tenant): array
    {
        $rows = [
            ['Bouquets', 'Seasonal hand-tied arrangements, cut to order the morning they ship.', '1487070183336-b863922373d4'], // florist stall
            ['Plants',   'Potted greenery for every corner of the home.',                         '1453904300235-0f2f60b15b5d'], // fiddle-leaf fig
            ['Dried',    'Everlasting stems and grasses that last for months.',                   '1466692476868-aef1dfb1e735'], // grasses
            ['Vases',    'Simple vessels to show your stems off.',                                 '1518895949257-7621c3c786d7'], // rose in a glass vase
        ];
        $out = [];
        foreach ($rows as $i => [$name, $desc, $kw]) {
            $slug = Str::slug($name);
            $out[$slug] = Category::create([
                'tenant_id'    => $tenant->id,
                'name'         => $name,
                'slug'         => $slug,
                'description'  => $desc,
                'image_path'   => $this->flower("demo/posy/cat-{$slug}.jpg", $kw, 1100 + $i, 1200, 800),
                'sort_order'   => $i,
                'is_active'    => true,
                'show_in_menu' => true,
            ]);
        }
        return $out;
    }

    /** @return array<int, Product> */
    private function seedProducts(Tenant $tenant, array $categories): array
    {
        // [name, description, price € cents, Unsplash photo ID (hand-verified subject), badge]
        $catalog = [
            'bouquets' => [
                ['The Wildling',   "A loose, garden-gathered bunch of the season's best — ranunculus, sweet pea, foraged greenery and a few happy surprises.", 6200, '1490750967868-88aa4486c946', 'Bestseller'], // yellow poppies
                ['Sunday Market',  "A cheerful market bunch of whatever's freshest that morning, wrapped in compostable paper.",                                  4800, '1457089328109-e5d9bd499191', ''],          // colourful bouquet
                ['Blush Garden',   'Soft pinks and creams — peonies, garden roses and a haze of astilbe.',                                                          5600, '1521543832500-49e69fb2bea2', ''],          // blush bridal bouquet
                ['The Brightside', 'Bold and sunny — ranunculus, tulips and pops of craspedia for the grey days.',                                                 5800, '1455659817273-f96807779a8a', 'New'],       // sunflowers
            ],
            'plants' => [
                ['Olive Tree',      'A young olive in a hand-thrown terracotta pot. Loves a bright, sunny sill.',  4800, '1459411552884-841db9b3cc2a', ''], // potted plant in terracotta
                ['Trailing Pothos', 'An easy, forgiving trailing plant that thrives in the dimmest corner.',      3200, '1453904300235-0f2f60b15b5d', ''], // fiddle-leaf fig
            ],
            'dried' => [
                ['Everlasting Bunch', 'A dried arrangement that lasts for months — bunny tails, statice and feathered grasses.', 3800, '1469259943454-aa100abba749', ''], // calla lilies
                ['Wheat & Grasses',   'Golden wheat and soft grasses, simply tied with twine.',                                  3400, '1466692476868-aef1dfb1e735', ''], // green grasses / seedlings
            ],
            'vases' => [
                ['Glass Bud Vase',      'A simple, clear hand-blown glass vase for a single stem or two.', 1800, '1518895949257-7621c3c786d7', ''], // rose in a glass vase
                ['Ceramic Footed Bowl', 'A handmade stoneware bowl, perfect for low, loose arrangements.', 3400, '1485955900006-10f4d324d411', ''], // succulent in a pot
            ],
        ];

        // Extra verified-flower shots for the bouquet PDP gallery + fullscreen
        // viewer, cycled so each product pages through distinct images.
        $galleryPool = [
            '1508610048659-a06b669e3321', // rainbow roses
            '1496062031456-07b8f162a322', // single red rose
            '1469259943454-aa100abba749', // calla lilies
            '1487070183336-b863922373d4', // florist stall
        ];

        $products = [];
        $lock = 10;
        $gi = 0;
        foreach ($catalog as $catSlug => $items) {
            $category = $categories[$catSlug] ?? null;
            if (! $category) {
                continue;
            }
            foreach ($items as $i => [$name, $desc, $price, $imgId, $badge]) {
                $slug = Str::slug($name);
                $stock = 12 + ($i * 5) % 24;
                $product = Product::create([
                    'tenant_id'      => $tenant->id,
                    'name'           => $name,
                    'slug'           => $slug,
                    'description'    => $desc,
                    'price_cents'    => $price,
                    'currency'       => 'EUR',
                    'stock_quantity' => $stock,
                    'image_path'     => $this->flower("demo/posy/{$slug}-1.jpg", $imgId, $lock++, 1000, 1100),
                    'is_active'      => true,
                ]);

                // Bouquets get two extra gallery shots so the PDP gallery +
                // fullscreen viewer have something to page through.
                if ($catSlug === 'bouquets') {
                    foreach ([2, 3] as $n) {
                        $poolId = $galleryPool[$gi++ % count($galleryPool)];
                        if ($path = $this->flower("demo/posy/{$slug}-{$n}.jpg", $poolId, $lock++, 1000, 1100)) {
                            ProductImage::create([
                                'product_id' => $product->id,
                                'path'       => $path,
                                'alt_text'   => "{$name} — view {$n}",
                                'sort_order' => $n - 1,
                            ]);
                        }
                    }
                }

                DB::table('category_product')->insert([
                    'category_id' => $category->id,
                    'product_id'  => $product->id,
                ]);

                $product->_catSlug = $catSlug;  // ferried to seedVariants
                $product->_badge   = $badge;
                $products[] = $product;
            }
        }
        return $products;
    }

    /** Bouquets get Petite / Classic / Lavish sizes; everything else is single-SKU. */
    private function seedVariants(array $products): void
    {
        foreach ($products as $product) {
            if (($product->_catSlug ?? null) !== 'bouquets') {
                continue;
            }
            $base = (int) $product->price_cents;
            $sizes = [
                ['Petite',  (int) round($base * 0.72 / 100) * 100, 18],
                ['Classic', $base,                                 9],
                ['Lavish',  (int) round($base * 1.42 / 100) * 100, 4],
            ];
            foreach ($sizes as $i => [$label, $priceCents, $stock]) {
                ProductVariant::create([
                    'product_id'     => $product->id,
                    'label'          => $label,
                    'sku'            => $product->slug . '-' . strtolower($label),
                    'price_cents'    => $priceCents,
                    'stock_quantity' => $stock,
                    'sort_order'     => $i,
                    'is_active'      => true,
                ]);
            }
        }
    }

    private function seedCollections(Tenant $tenant, array $products): void
    {
        if (empty($products)) {
            return;
        }
        $by = collect($products);
        $bouquets = $by->filter(fn ($p) => ($p->_catSlug ?? null) === 'bouquets')->values();
        // Cheaper picks across categories for the sale rail.
        $sale = $by->filter(fn ($p) => $p->price_cents <= 4000)->values();

        $gathering = Collection::create([
            'tenant_id'    => $tenant->id,
            'title'        => "This week's gathering",
            'slug'         => 'this-week',
            'description'  => 'The freshest bunches in the studio right now, cut to order.',
            'banner_path'  => $this->flower('demo/posy/coll-this-week.jpg', '1457089328109-e5d9bd499191', 60, 1600, 720), // colourful bouquet
            'sort_order'   => 0,
            'is_featured'  => true,
            'is_active'    => true,
            'show_in_menu' => true,
        ]);
        $this->attach($gathering, $bouquets);

        $onSale = Collection::create([
            'tenant_id'    => $tenant->id,
            'title'        => 'On sale',
            'slug'         => 'on-sale',
            'description'  => 'A few stems and pots at a gentle price while they last.',
            'banner_path'  => $this->flower('demo/posy/coll-on-sale.jpg', '1508610048659-a06b669e3321', 61, 1600, 720), // rainbow roses
            'sort_order'   => 1,
            'is_featured'  => true,
            'is_active'    => true,
            'show_in_menu' => true,
        ]);
        $this->attach($onSale, $sale);
    }

    private function attach(Collection $collection, iterable $products): void
    {
        $sort = 0;
        foreach ($products as $p) {
            DB::table('collection_product')->insert([
                'collection_id' => $collection->id,
                'product_id'    => $p->id,
                'sort_order'    => $sort++,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    private function configureStore(Store $store): void
    {
        $store->update([
            'theme'              => 'posy',
            'primary_color'      => '#4a6b3c',
            'is_live'            => true,
            'currency'           => 'EUR',
            'display_currencies' => ['EUR', 'USD', 'GBP', 'BGN'],
            'fx_rates'           => ['USD' => 1.09, 'GBP' => 0.86],
            'announcement'       => [
                'enabled' => true,
                'text'    => 'Cut to order daily · Same-day delivery before 2pm · Stems from local growers',
                'link'    => null,
                'speed'   => 'normal',
            ],
            'nav_menu' => [
                ['label' => 'Shop',        'url' => '/',            'sort_order' => 0, 'auto_source' => null,          'children' => []],
                ['label' => 'Bouquets',    'url' => '/categories/bouquets', 'sort_order' => 1, 'auto_source' => null,  'children' => []],
                ['label' => 'Collections', 'url' => null,           'sort_order' => 2, 'auto_source' => 'collections', 'children' => []],
                ['label' => 'Sale',        'url' => '/collections/on-sale', 'sort_order' => 3, 'auto_source' => null,  'children' => []],
            ],
            'hero_banner' => [
                'enabled'    => true,
                'title'      => 'Seasonal · locally grown',
                'subtitle'   => 'Flowers, freshly gathered.',
                'image_path' => null,
                'cta_label'  => 'Shop bouquets',
                'cta_url'    => '#shop',
            ],
        ]);
    }

    /**
     * Download a keyword-tagged flower photo from loremflickr once (cached
     * on disk; consistent per `lock`). Returns null on failure so the Posy
     * theme falls back to its own leaf/bloom gradient placeholders rather
     * than an ugly broken image.
     */
    /**
     * Download a curated florist photo by its Unsplash photo ID.
     *
     * loremflickr proved unusable for this set — it ANDed comma-separated tags,
     * returned off-topic, duplicated photos (a cat statue for "wheat"), and
     * randomly injected a pure-red band into the top of ~1-in-7 images. So
     * every image here is a HAND-VERIFIED Unsplash photo (stable CDN, no
     * watermarks, correct florist subject). Picsum is a clean fallback if
     * Unsplash is unreachable; null → the theme's bloom/leaf gradient.
     */
    private function flower(string $storagePath, string $photoId, int $lock, int $width, int $height): ?string
    {
        $disk = Storage::disk('public');
        if ($disk->exists($storagePath)) {
            return $storagePath;
        }
        $sources = [
            "https://images.unsplash.com/photo-{$photoId}?w={$width}&h={$height}&fit=crop&q=72",
            "https://picsum.photos/seed/posy-{$lock}/{$width}/{$height}.jpg",
        ];
        $ctx = stream_context_create(['http' => ['timeout' => 15, 'follow_location' => 1]]);
        foreach ($sources as $url) {
            $bytes = @file_get_contents($url, false, $ctx);
            if ($bytes !== false && strlen($bytes) > 800) {
                $disk->put($storagePath, $bytes);
                return $storagePath;
            }
        }
        $this->command->warn("  no image for {$storagePath} — using theme placeholder");
        return null;
    }
}
