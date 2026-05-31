<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Populate the demo tenant (`relic`) with a believable boutique storefront
 * so designs can be evaluated against realistic data:
 *
 *   - 5 categories (Apparel, Footwear, Accessories, Outerwear, Essentials)
 *   - 20 products spread across the categories
 *   - 2 gallery images per product (downloaded once from picsum.photos with
 *     stable seeds so re-running gives identical files; stored under
 *     storage/app/public/demo/ so Storage::url() resolves correctly)
 *   - Size variants for apparel + outerwear, EU sizes for footwear
 *   - 3 featured collections (Spring '26, Best of Apparel, On Sale)
 *   - One discount code (WELCOME10 — 10% off, €50 minimum)
 *   - Store chrome: announcement bar, nav menu pointing at categories,
 *     hero banner with copy + CTA
 *
 * Idempotent — wiping the tenant's existing products / categories /
 * collections / discounts first, so re-running gives a clean slate.
 *
 * Not registered in DatabaseSeeder by design: the image download step
 * shouldn't fire on every `migrate:fresh --seed`. Invoke explicitly:
 *
 *   docker exec ganvo php artisan db:seed --class=DemoStorefrontSeeder
 */
class DemoStorefrontSeeder extends Seeder
{
    /** Slug of the tenant to populate. Override via DEMO_TENANT_SLUG. */
    private const DEFAULT_TENANT_SLUG = 'relic';

    public function run(): void
    {
        $slug = env('DEMO_TENANT_SLUG', self::DEFAULT_TENANT_SLUG);
        /** @var Tenant|null $tenant */
        $tenant = Tenant::where('slug', $slug)->first();
        if (! $tenant) {
            $this->command->error("No tenant with slug '{$slug}'. Set DEMO_TENANT_SLUG or create the tenant first.");
            return;
        }
        $store = Store::firstOrCreate(['tenant_id' => $tenant->id]);

        $this->command->info("Seeding demo storefront for tenant '{$slug}' (id={$tenant->id})…");

        $this->wipeExisting($tenant);
        Storage::disk('public')->makeDirectory('demo/products');
        Storage::disk('public')->makeDirectory('demo/categories');
        Storage::disk('public')->makeDirectory('demo/collections');
        Storage::disk('public')->makeDirectory('demo/hero');

        $categories  = $this->seedCategories($tenant);
        $products    = $this->seedProducts($tenant, $categories);
        $this->seedVariants($products);
        $this->seedCollections($tenant, $products);
        $this->seedDiscount($tenant);
        $this->configureStoreChrome($store, $categories);

        $this->command->info('Done.');
        $this->command->info('  Categories: ' . count($categories));
        $this->command->info('  Products:   ' . count($products));
        $this->command->info('  Storefront: http://' . $slug . '.' . config('ganvo.central_domain') . ':8000');
    }

    private function wipeExisting(Tenant $tenant): void
    {
        // Drop everything we're about to re-seed so the tenant ends up in a
        // known state. Carts / orders that reference products by id are
        // left alone — the seeder won't re-use product ids, so any stale
        // refs become orphaned (acceptable for dev data).
        $productIds = Product::where('tenant_id', $tenant->id)->withTrashed()->pluck('id');
        ProductImage::whereIn('product_id', $productIds)->delete();
        ProductVariant::whereIn('product_id', $productIds)->delete();
        DB::table('collection_product')->whereIn('product_id', $productIds)->delete();
        DB::table('category_product')->whereIn('product_id', $productIds)->delete();
        Product::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();

        Collection::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
        Category::where('tenant_id', $tenant->id)->withTrashed()->forceDelete();
        Discount::where('tenant_id', $tenant->id)->forceDelete();
    }

    /**
     * @return array<string, Category>  slug => Category
     */
    private function seedCategories(Tenant $tenant): array
    {
        $rows = [
            ['Apparel',     'Hoodies, tees, knitwear — everyday building blocks.'],
            ['Footwear',    'Sneakers, slip-ons, boots. Built to live in.'],
            ['Accessories', 'Caps, bags, leather goods. The small things.'],
            ['Outerwear',   'Jackets, vests, and coats for every shoulder season.'],
            ['Essentials',  'Socks, undershirts, beanies — quiet workhorses.'],
        ];
        $out = [];
        foreach ($rows as $i => [$name, $desc]) {
            $slug = \Illuminate\Support\Str::slug($name);
            $imagePath = $this->downloadPicsum(
                "demo/categories/{$slug}.jpg",
                "ganvo-cat-{$slug}",
                1200, 800,
            );
            $out[$slug] = Category::create([
                'tenant_id'   => $tenant->id,
                'name'        => $name,
                'slug'        => $slug,
                'description' => $desc,
                'image_path'  => $imagePath,
                'sort_order'  => $i,
                'is_active'   => true,
            ]);
        }
        return $out;
    }

    /**
     * @param  array<string, Category> $categories
     * @return array<int, Product>
     */
    private function seedProducts(Tenant $tenant, array $categories): array
    {
        // Per-category catalog. Price in EUR cents, stock random-but-realistic.
        // Keep each name distinct and plausible so the rendered store looks
        // like a real boutique rather than "demo product 1, demo product 2".
        $catalog = [
            'apparel' => [
                ['Heavy Knit Crewneck',     'Chunky merino-blend pullover with a relaxed body and ribbed cuffs.', 8500],
                ['Boxy Logo Tee',           'Heavyweight cotton tee with a small chest mark. Drops past the hip.', 3800],
                ['Relaxed Hoodie',          'Brushed-back fleece, double-lined hood, dropped shoulders.',          9500],
                ['Oversized Long Sleeve',   'Soft slub-jersey with a slightly extended hem.',                       4500],
            ],
            'footwear' => [
                ['Court Sneaker Low',       'Full-grain leather upper on a vulcanized gum sole.',                  12000],
                ['Suede Slip-on',           'Buttery Italian suede, elasticated gussets, leather-lined.',          14500],
                ['Chunky Trail Boot',       'Waxed canvas and rubber, contrast lug sole.',                         21000],
                ['Canvas High Top',         'Heavy 14oz canvas, vulcanized construction, removable insole.',        8500],
            ],
            'accessories' => [
                ['Six-panel Cap',           'Washed cotton twill, brass buckle, embroidered logo.',                 3200],
                ['Canvas Tote',             'Roomy 18oz tote with internal pocket and reinforced base.',            4800],
                ['Webbed Belt',             'Heavy-duty webbed belt with a matte-finish buckle.',                   3600],
                ['Leather Card Holder',     'Bridle leather, four card slots, naturally patinates.',                6800],
            ],
            'outerwear' => [
                ['Down Puffer Jacket',      '800-fill power down, lightweight ripstop shell, water-repellent.',    28000],
                ['Coach Jacket',            'Boxy cut, snap front, ribbed collar. The 90s reissue.',               17500],
                ['Wool Topcoat',            'Mid-length single-breasted topcoat in Italian wool.',                  39500],
                ['Quilted Vest',            'Diamond-quilted shell with recycled-down fill.',                       14500],
            ],
            'essentials' => [
                ['Premium Crew Socks (3-pack)', 'Pima-cotton blend ribbed crew socks.',                             1800],
                ['Heavyweight Undershirt',  'Loop-wheeled cotton, mid-weight, lasts forever.',                      2800],
                ['Sherpa Beanie',           'Wool-blend rib with a sherpa-lined band.',                             4200],
                ['Merino Scarf',            '100% merino, fringed ends, soft hand.',                                6800],
            ],
        ];

        $products = [];
        foreach ($catalog as $catSlug => $items) {
            $category = $categories[$catSlug] ?? null;
            if (! $category) continue;

            foreach ($items as $i => [$name, $description, $priceCents]) {
                $slug = \Illuminate\Support\Str::slug($name);
                // Primary image goes in image_path; two gallery images go in
                // product_images. The shared product-gallery partial reads
                // both — see Product::allImages().
                $primary = $this->downloadPicsum(
                    "demo/products/{$slug}-1.jpg",
                    "ganvo-prod-{$slug}-1",
                    1000, 1250,
                );
                $gallery2 = $this->downloadPicsum(
                    "demo/products/{$slug}-2.jpg",
                    "ganvo-prod-{$slug}-2",
                    1000, 1250,
                );
                $gallery3 = $this->downloadPicsum(
                    "demo/products/{$slug}-3.jpg",
                    "ganvo-prod-{$slug}-3",
                    1000, 1250,
                );

                $stock = 20 + ($i * 7) % 40;  // deterministic 20–60
                $product = Product::create([
                    'tenant_id'      => $tenant->id,
                    'name'           => $name,
                    'slug'           => $slug,
                    'description'    => $description,
                    'price_cents'    => $priceCents,
                    'currency'       => 'EUR',
                    'stock_quantity' => $stock,
                    'image_path'     => $primary,
                    'is_active'      => true,
                ]);

                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $gallery2,
                    'alt_text'   => $name . ' — alternate view',
                    'sort_order' => 1,
                ]);
                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $gallery3,
                    'alt_text'   => $name . ' — detail',
                    'sort_order' => 2,
                ]);

                // Attach to the parent category. The category_product pivot
                // is many-to-many; relic uses simple 1:1 here.
                DB::table('category_product')->insert([
                    'category_id' => $category->id,
                    'product_id'  => $product->id,
                ]);

                $products[] = $product;
            }
        }
        return $products;
    }

    /**
     * @param  array<int, Product> $products
     */
    private function seedVariants(array $products): void
    {
        $apparelSizes  = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $shoeSizes     = ['39', '40', '41', '42', '43', '44', '45'];
        $apparelLike   = ['apparel', 'outerwear', 'essentials'];

        foreach ($products as $product) {
            $catSlug = optional(DB::table('category_product')
                ->join('categories', 'category_product.category_id', '=', 'categories.id')
                ->where('category_product.product_id', $product->id)
                ->first())->slug;

            if ($catSlug === 'footwear') {
                $sizes = $shoeSizes;
            } elseif (in_array($catSlug, $apparelLike, true)
                     && ! str_contains(strtolower($product->name), 'sock')
                     && ! str_contains(strtolower($product->name), 'cap')
                     && ! str_contains(strtolower($product->name), 'beanie')
                     && ! str_contains(strtolower($product->name), 'scarf')
                     && ! str_contains(strtolower($product->name), 'tote')
                     && ! str_contains(strtolower($product->name), 'belt')
                     && ! str_contains(strtolower($product->name), 'holder')) {
                $sizes = $apparelSizes;
            } else {
                continue;  // single-SKU: caps, bags, leather goods, socks
            }

            foreach ($sizes as $i => $label) {
                // First size always in stock; last one out of stock so the
                // UI's out-of-stock state has somewhere to render. The rest
                // get a low-stock count for variety.
                $stock = match (true) {
                    $i === 0                       => 25,
                    $i === count($sizes) - 1       => 0,
                    default                        => 3 + (($i * 4) % 12),
                };
                ProductVariant::create([
                    'product_id'     => $product->id,
                    'label'          => $label,
                    'sku'            => $product->slug . '-' . strtolower($label),
                    'price_cents'    => null, // inherit from product
                    'stock_quantity' => $stock,
                    'sort_order'     => $i,
                    'is_active'      => true,
                ]);
            }
        }
    }

    /**
     * @param  array<int, Product> $products
     */
    private function seedCollections(Tenant $tenant, array $products): void
    {
        if (empty($products)) return;
        $by = collect($products);

        // 1) Spring '26 — curated mix of 8 across categories
        $springPicks = $by->take(2)
            ->merge($by->slice(4, 2))
            ->merge($by->slice(8, 2))
            ->merge($by->slice(12, 2))
            ->values();
        $spring = Collection::create([
            'tenant_id'    => $tenant->id,
            'title'        => 'Spring ’26',
            'slug'         => 'spring-26',
            'description'  => 'Fresh pieces for the warmer months — knits that breathe, soft-soled shoes, and lighter outerwear.',
            'banner_path'  => $this->downloadPicsum(
                'demo/collections/spring-26.jpg',
                'ganvo-coll-spring-26',
                1600, 720,
            ),
            'sort_order'   => 0,
            'is_featured'  => true,
            'is_active'    => true,
        ]);
        $this->attachToCollection($spring, $springPicks);

        // 2) Best of Apparel
        $apparel = $by->slice(0, 4);
        $bestApparel = Collection::create([
            'tenant_id'    => $tenant->id,
            'title'        => 'Best of Apparel',
            'slug'         => 'best-of-apparel',
            'description'  => 'Our most-loved everyday layers.',
            'banner_path'  => $this->downloadPicsum(
                'demo/collections/best-of-apparel.jpg',
                'ganvo-coll-best-apparel',
                1600, 720,
            ),
            'sort_order'   => 1,
            'is_featured'  => true,
            'is_active'    => true,
        ]);
        $this->attachToCollection($bestApparel, $apparel);

        // 3) On Sale — handpicked 4 products
        $onSale = $by->only([3, 7, 11, 15])->values();
        $sale = Collection::create([
            'tenant_id'    => $tenant->id,
            'title'        => 'On sale',
            'slug'         => 'on-sale',
            'description'  => 'Limited-time picks while they last.',
            'banner_path'  => $this->downloadPicsum(
                'demo/collections/on-sale.jpg',
                'ganvo-coll-on-sale',
                1600, 720,
            ),
            'sort_order'   => 2,
            'is_featured'  => true,
            'is_active'    => true,
        ]);
        $this->attachToCollection($sale, $onSale);
    }

    private function attachToCollection(Collection $collection, iterable $products): void
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

    private function seedDiscount(Tenant $tenant): void
    {
        // Wraps WELCOME10 — 10% off, €50 minimum, applicable storefront-wide.
        // is_auto=false so customers paste the code at cart; usage_limit is
        // generous so dev testing doesn't burn through it.
        Discount::create([
            'tenant_id'           => $tenant->id,
            'name'                => 'Welcome 10% off',
            'code'                => 'WELCOME10',
            'type'                => Discount::TYPE_PERCENTAGE,
            'value'               => 10,
            'min_subtotal_cents'  => 5000,
            'starts_at'           => null,
            'ends_at'             => null,
            'usage_limit'         => 1000,
            'per_customer_limit'  => 1,
            'times_used'          => 0,
            'is_auto'             => false,
            'is_active'           => true,
        ]);
    }

    /**
     * @param  array<string, Category> $categories
     */
    private function configureStoreChrome(Store $store, array $categories): void
    {
        $heroImage = $this->downloadPicsum(
            'demo/hero/spring-banner.jpg',
            'ganvo-hero-spring-26',
            2000, 900,
        );

        // Grouped nav with AUTO-populated dropdowns. The Categories and
        // Collections rows use auto_source so they stay in sync with the
        // live category / collection list (filtered by show_in_menu) —
        // no double-bookkeeping when the merchant adds a new category.
        $nav = [
            [
                'label'       => 'Shop',
                'url'         => '/',
                'sort_order'  => 0,
                'auto_source' => null,
                'children'    => [],
            ],
            [
                'label'       => 'Categories',
                'url'         => null,
                'sort_order'  => 1,
                'auto_source' => 'categories',
                'children'    => [],
            ],
            [
                'label'       => 'Collections',
                'url'         => null,
                'sort_order'  => 2,
                'auto_source' => 'collections',
                'children'    => [],
            ],
            [
                'label'       => 'Sale',
                'url'         => '/collections/on-sale',
                'sort_order'  => 3,
                'auto_source' => null,
                'children'    => [],
            ],
        ];

        $store->update([
            'is_live'       => true,
            'announcement'  => [
                'enabled' => true,
                'text'    => 'Free shipping on orders over €100 · WELCOME10 for 10% off your first order',
                'link'    => null,
            ],
            'nav_menu'      => $nav,
            'hero_banner'   => [
                'enabled'    => true,
                'title'      => 'Spring ’26 collection',
                'subtitle'   => 'Lighter knits, soft-soled shoes, and the outerwear you actually want now.',
                'image_path' => $heroImage,
                'cta_label'  => 'Shop the collection',
                'cta_url'    => '/collections/spring-26',
            ],
        ]);
    }

    /**
     * Download a picsum image once and store under storage/app/public.
     * Skips the network call when the file already exists, so re-runs of
     * the seeder are fast. Returns the storage path suitable for
     * image_path / banner_path / hero_banner.image_path columns.
     *
     * Falls back to a placeholder SVG when the download fails (offline,
     * picsum down, etc.) so the seeder always completes.
     */
    private function downloadPicsum(string $storagePath, string $seed, int $width, int $height): string
    {
        $disk = Storage::disk('public');
        if ($disk->exists($storagePath)) {
            return $storagePath;
        }

        $url = "https://picsum.photos/seed/{$seed}/{$width}/{$height}.jpg";
        // Stream the image into memory then persist. cURL via file_get_contents
        // is good enough for a few dozen small JPGs.
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'follow_location' => 1]]);
        $bytes = @file_get_contents($url, false, $ctx);
        if ($bytes === false || strlen($bytes) < 100) {
            // Fallback — write a tiny placeholder SVG so the storefront still
            // renders something. Better than null when picsum is down.
            $svg = sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d"><rect width="100%%" height="100%%" fill="#e7e5e4"/><text x="50%%" y="50%%" font-family="sans-serif" font-size="36" fill="#a8a29e" text-anchor="middle" dominant-baseline="middle">%s</text></svg>',
                $width, $height, htmlspecialchars($seed)
            );
            $disk->put($storagePath, $svg);
            $this->command->warn("  picsum unavailable for '{$seed}' — wrote SVG placeholder");
            return $storagePath;
        }

        $disk->put($storagePath, $bytes);
        return $storagePath;
    }
}
