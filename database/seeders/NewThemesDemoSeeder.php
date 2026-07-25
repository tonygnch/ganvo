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
    /**
     * Create the demo owner + shopper accounts (both with the password
     * "password"). TRUE for local demo seeding; the production provisioning
     * command turns it OFF, because planting a known-password store admin on
     * a public site is a live vulnerability, and the real owner already has
     * an account created through onboarding.
     */
    public bool $createAccounts = true;

    public function run(): void
    {
        foreach ($this->stores() as $slug => $cfg) {
            $this->seedStore($slug, $cfg);
        }

        // Sankevi is the option-matrix showcase: its boards are chosen by
        // length AND width, and the two interact.
        $this->seedSankeviMatrix();
    }

    /** Console output that survives being run outside `db:seed`. */
    private function say(string $msg): void
    {
        if ($this->command) {
            $this->command->info($msg);
        }
    }

    /** Build ONE demo store by slug — the entry point the provisioning command uses. */
    public function seedOnly(string $slug): void
    {
        $all = $this->stores();
        if (! isset($all[$slug])) {
            throw new \InvalidArgumentException("No demo config for store '{$slug}'.");
        }
        $this->seedStore($slug, $all[$slug]);
        if ($slug === 'sankevi') {
            $this->seedSankeviMatrix();
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

        if ($this->createAccounts) {
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
        }

        $this->say("Seeding {$cfg['name']} ({$slug})…");
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
                'stock_quantity' => 24,
                // Art-directed demo photography, committed to the repo under
                // public/images/demo/{theme}/ and copied into storage so the
                // demo is deterministic (no runtime downloads). Products
                // without a shot fall back to the theme's placeholder motif.
                // Per-product shot first, then the product's CATEGORY shot. The
                // category fallback is what stops a renamed product silently
                // losing its photograph: demo_images is keyed by Str::slug() of
                // the NAME, so editing a name breaks the key with no error, and
                // the row quietly falls back to the placeholder motif. That had
                // already happened twice here. A family photograph is also a
                // truthful stand-in — every board in Греди looks like Греди.
                'image_path' => $this->demoImage(
                    $slug,
                    $pslug,
                    $cfg['demo_images'][$pslug]
                        ?? ($cfg['demo_images_by_cat'][$catSlug] ?? null)
                ),
                'is_active' => true,
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
                'description' => $desc,
                'banner_path' => $this->demoImage($slug, "coll-{$cslug}", $cfg['collection_banner'] ?? null),
                'sort_order' => 0,
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
        $this->say('  → http://' . $slug . '.' . config('ganvo.central_domain') . ':8000  (' . count($products) . ' products)');
    }

    /**
     * Sankevi's dependent options — the reason the matrix exists.
     *
     * Each board is sold by Дължина × Широчина, but not every pairing is
     * milled: a 4 m board is only worth cutting in the wider sections. The
     * impossible pairs are not configured anywhere — they are simply the
     * combinations with no variant, which is what the picker greys out.
     */
    private function seedSankeviMatrix(): void
    {
        $tenant = Tenant::where('slug', 'sankevi')->first();
        if (! $tenant) {
            return;
        }

        // length => the widths actually milled at that length
        $matrix = [
            '2000 мм' => ['96 мм', '121 мм'],
            '3000 мм' => ['121 мм', '146 мм'],
            '4000 мм' => ['146 мм', '196 мм'],
        ];

        // Furniture is made to order, not cut from a section list, so it keeps a
        // plain price and stays off the dimension matrix.
        $skip = Category::where('tenant_id', $tenant->id)->where('slug', 'mebeli')->value('id');
        $products = Product::where('tenant_id', $tenant->id)
            ->when($skip, fn ($q) => $q->whereDoesntHave('categories', fn ($c) => $c->where('categories.id', $skip)))
            ->get();
        foreach ($products as $product) {
            $product->variants()->delete();
            $product->options()->delete();

            $lenOpt = \App\Models\ProductOption::create([
                'product_id' => $product->id, 'name' => 'Дължина', 'sort_order' => 0,
            ]);
            $widOpt = \App\Models\ProductOption::create([
                'product_id' => $product->id, 'name' => 'Широчина', 'sort_order' => 1,
            ]);

            $lenVals = [];
            foreach (array_keys($matrix) as $i => $len) {
                $lenVals[$len] = \App\Models\ProductOptionValue::create([
                    'product_option_id' => $lenOpt->id, 'value' => $len, 'sort_order' => $i,
                ]);
            }
            $widVals = [];
            foreach (['96 мм', '121 мм', '146 мм', '196 мм'] as $i => $w) {
                $widVals[$w] = \App\Models\ProductOptionValue::create([
                    'product_option_id' => $widOpt->id, 'value' => $w, 'sort_order' => $i,
                ]);
            }

            $sort = 0;
            foreach ($matrix as $len => $widths) {
                foreach ($widths as $w) {
                    // price scales with the cross-section, rounded to whole cents
                    $lenM = (int) filter_var($len, FILTER_SANITIZE_NUMBER_INT) / 1000;
                    $widM = (int) filter_var($w, FILTER_SANITIZE_NUMBER_INT) / 1000;
                    $price = (int) round($product->price_cents * $lenM * ($widM / 0.121) / 50) * 50;

                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'label' => $len . ' / ' . $w,
                        'sku' => $product->slug . '-' . preg_replace('/\D/', '', $len) . 'x' . preg_replace('/\D/', '', $w),
                        'price_cents' => $price,
                        'stock_quantity' => 40 - ($sort * 3),
                        'sort_order' => $sort++,
                        'is_active' => true,
                    ]);
                    $variant->optionValues()->attach($lenVals[$len]->id, ['product_option_id' => $lenOpt->id]);
                    $variant->optionValues()->attach($widVals[$w]->id, ['product_option_id' => $widOpt->id]);
                }
            }
        }

        $this->say('  → Sankevi option matrix: ' . count($matrix) . ' lengths × varying widths on ' . $products->count() . ' products');
    }

    private function configureStore(Store $store, string $slug, array $cfg): void
    {
        // Sankevi is a Bulgarian-first client site — its nav should not open in English.
        $shopLabel = $cfg['shop_label'] ?? 'Shop';
        // A theme that ships its own catalogue view has split the brand page
        // from the shop (StorefrontController::shop renders it at /shop), so
        // the merchant's "Shop" entry must point at the catalogue rather than
        // at a landing page that no longer carries a product grid.
        $shopUrl = view()->exists("themes.{$slug}.shop") ? '/shop' : '/';

        if (! empty($cfg['nav'])) {
            // A short, curated header. Once the catalogue lives at /shop and the
            // brand page carries the family rows, listing every category up top
            // just crowds the masthead — the families are one click away either
            // way. Merchants can rebuild this in Store settings → Header menu.
            $nav = [];
            foreach ($cfg['nav'] as $i => [$label, $url]) {
                $nav[] = ['label' => $label, 'url' => $url, 'sort_order' => $i, 'auto_source' => null, 'children' => []];
            }
        } else {
            $nav = [['label' => $shopLabel, 'url' => $shopUrl, 'sort_order' => 0, 'auto_source' => null, 'children' => []]];
            $so = 1;
            foreach ($cfg['cats'] as $cslug => $c) {
                if ($c[0] === 'Shop') {
                    continue; // Forma's lone category is literally "Shop" — skip the duplicate nav entry
                }
                $nav[] = ['label' => $c[0], 'url' => "/categories/{$cslug}", 'sort_order' => $so++, 'auto_source' => null, 'children' => []];
            }
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
            // Contact page: filled in where the demo defines details, otherwise
            // the page still renders on the tenant's own email/phone.
            'about' => $cfg['about'] ?? null,
            'contact' => array_merge([
                'enabled' => true,
                'show_form' => true,
                'heading' => '',
                'intro' => '',
                'address' => '',
                'phone' => '',
                'email' => '',
                'hours' => '',
                'map_embed' => '',
            ], $cfg['contact'] ?? []),
        ]);
    }

    /** Copy a committed demo photo (public/…) into the public storage disk. */
    private function demoImage(string $storeSlug, string $productSlug, ?string $publicRelPath): ?string
    {
        if (! $publicRelPath || ! is_file(public_path($publicRelPath))) {
            return null;
        }
        $target = "demo/{$storeSlug}/{$productSlug}-1." . pathinfo($publicRelPath, PATHINFO_EXTENSION);
        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $target,
            file_get_contents(public_path($publicRelPath))
        );

        return $target;
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
                // Higgsfield art-directed set (one locked brief, per-subject
                // prompts) — blank vessels only; the theme's CSS labels speak.
                'demo_images' => [
                    'hearth' => 'images/demo/wick/hearth-1.jpg',
                    'library' => 'images/demo/wick/library-1.jpg',
                    'orchard-dusk' => 'images/demo/wick/orchard-dusk-1.jpg',
                    'black-honey' => 'images/demo/wick/black-honey-1.jpg',
                    'chapel-incense' => 'images/demo/wick/chapel-incense-1.jpg',
                    'sea-fog-room-spray' => 'images/demo/wick/sea-fog-room-spray-1.jpg',
                    'brass-wick-trimmer' => 'images/demo/wick/brass-wick-trimmer-1.jpg',
                ],
                'collection_banner' => 'images/demo/wick/banner-1.jpg',
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
            'timber' => [
                'name' => 'Timberline', 'accent' => '#b57a34', 'currency' => 'EUR',
                // No demo photography — the theme renders its own plank-mark
                // placeholder + spec plates, so blank products show the design.
                'announce' => 'Kiln-dried & pressure-treated · Cut to length · Trade accounts welcome',
                'contact' => [
                    'heading' => 'Come to the yard',
                    'intro' => 'Call ahead for cutting lists and bulk pricing, or send the drawings over and we will quote the full order.',
                    'address' => "Timberline Yard\nInd. zone Iztok, 14 Purvi Mai St\n1528 Sofia, Bulgaria",
                    'phone' => '+359 2 555 0184',
                    'hours' => "Mon–Fri  07:00–18:00\nSat  08:00–14:00\nSun  closed",
                ],
                'hero' => ['Pressure-treated timber', 'Built to last outdoors.', 'Shop the yard'],
                // Timber sells by length — variants are the stocked lengths.
                'sizes' => [['2.4 m', 0.8, 60], ['3.0 m', 1.0, 45], ['3.6 m', 1.2, 30], ['4.8 m', 1.6, 16]],
                'cats' => [
                    'decking' => ['Decking', 'Pressure-treated deck boards, joists and balustrade.'],
                    'fencing' => ['Fencing & Posts', 'Posts, rails and boards for garden fencing.'],
                    'structural' => ['Structural Timber', 'Graded C16/C24 carcassing and CLS studwork.'],
                    'cladding' => ['Cladding & Sleepers', 'Exterior cladding, sleepers and landscaping timber.'],
                ],
                'products' => [
                    ['decking', 'Treated Decking Board 28×145', 'Smooth-faced pressure-treated deck board, UC3, ready to lay. Reversible: smooth or grooved.', 1250, true],
                    ['decking', 'Grooved Deck Board 32×150', 'Anti-slip grooved profile in tanalised softwood, UC3, 15-year warranty against rot.', 1490, true],
                    ['decking', 'Deck Joist 47×100 C16', 'Treated structural joist for deck sub-frames, UC4, take-up to 600 mm centres.', 890, true],
                    ['fencing', 'Fence Post 75×75 UC4', 'Incised, pressure-treated to Use Class 4 for direct ground contact. 15-year desired service life.', 1800, true],
                    ['fencing', 'Feather Edge Board 100×11', 'Tapered treated board for closeboard fencing, UC3. Sold per board.', 340, true],
                    ['fencing', 'Gravel Board 150×22', 'Treated base board keeps fence panels off the wet ground.', 760, true],
                    ['structural', 'CLS Stud 38×63 C16', 'Planed, eased-edge stud for internal partitions and studwork.', 520, true],
                    ['structural', 'C24 Carcassing 47×100', 'Kiln-dried, strength-graded C24 for floor joists and roof structures.', 940, true],
                    ['structural', 'C24 Carcassing 47×150', 'Heavier C24 section for wider spans and load-bearing work.', 1380, true],
                    ['cladding', 'Shiplap Cladding 19×125', 'Treated shiplap profile for sheds, garden rooms and facades, UC3.', 680, true],
                    ['cladding', 'Railway Sleeper 200×100', 'New brown treated softwood sleeper, UC4 — beds, steps and retaining walls.', 3200, false],
                    ['cladding', 'Log Roll Border 1.8 m', 'Half-round treated log roll for edging beds and paths.', 1450, false],
                ],
                'collection' => ['new-in-the-yard', 'New in the yard', 'Fresh stock, just off the delivery.'],
            ],
            'sankevi' => [
                'name' => 'Sankevi', 'accent' => '#8a9a5b', 'currency' => 'EUR',
                // The stock book at /shop is one full-width band per item, so
                // every row is carried by its photograph. Keyed by the product
                // slug Str::slug() derives from the Cyrillic name; subjects are
                // matched to what the item actually is, and the end-grain shot
                // sits far from the masthead that also uses it.
                // Shots picked for a specific item. Anything not listed here falls
                // through to its family below, so this map is now an override
                // rather than the only source — and a stale key costs a nicety,
                // not the photograph.
                'demo_images' => [
                    'greda-iglolistna-c24'   => '/images/demo/sankevi/yard.webp',
                    'letva-skara'            => '/images/demo/sankevi/workshop.webp',
                    'deking-impregniran'     => '/images/demo/sankevi/deck.webp',
                    'podkonstrukciia-deking' => '/images/demo/sankevi/beams.webp',
                ],
                // One photograph per family, keyed by CATEGORY slug — stable
                // across product renames in a way the map above is not.
                'demo_images_by_cat' => [
                    'gredi'    => '/images/demo/sankevi/beams.webp',
                    'letvi'    => '/images/demo/sankevi/letvi.webp',
                    'daski'    => '/images/demo/sankevi/floor.webp',
                    'lamperia' => '/images/demo/sankevi/lamperia.webp',
                    'pervazi'  => '/images/demo/sankevi/pervazi.webp',
                    'mebeli'   => '/images/demo/sankevi/mebeli.webp',
                    'dekingi'  => '/images/demo/sankevi/deck.webp',
                ],
                'shop_label' => 'Магазин',
                'nav' => [
                    ['Начало', '/'],
                    ['Магазин', '/shop'],
                    ['За нас', '/about'],
                    ['Контакти', '/contact'],
                ],
                'announce' => 'Семейна дъскорезница в Родопите · Собствен добив · Разкрой по размер',
                // The banner OVERRIDES the theme's own hero copy, so it has to
                // carry the same message the theme was rewritten to: what the
                // yard does for the caller, not where the trees grew. Leave it
                // blank and the theme's hero_h1_html shows through instead.
                // Subtitle deliberately BLANK. The banner's subtitle is plain
                // text, so setting it here would override the theme's own
                // hero_h1_html and lose the <em> that puts the accent colour
                // on "вашия размер" — the one coloured word in the headline.
                // Empty means the theme copy shows through, accent and all.
                'hero' => ['Семейна дъскорезница · Родопите', '', 'Разгледай материала'],
                // Sankevi sells by length AND width, and the two interact —
                // seedOptionMatrix() below wires the real dependency.
                'sizes' => [],
                // The yard's real families. Order matters: the brand page maps its
                // family photography by category order, so these line up with
                // beams / letvi / floor / lamperia / pervazi / mebeli / deck.
                'cats' => [
                    'gredi' => ['Греди', 'Носещи елементи от иглолистна дървесина, сортирани по якост.'],
                    'letvi' => ['Летви', 'Летви за скара, покрив и подконструкция.'],
                    'daski' => ['Дъски', 'Бичени и рендосани дъски, сушени в камера.'],
                    'lamperia' => ['Ламперия', 'Профилирана обшивка за стени и тавани.'],
                    'pervazi' => ['Первази', 'Первази, кантове и профили за завършване.'],
                    'mebeli' => ['Мебели', 'Масивни мебели по поръчка от собствена дървесина.'],
                    'dekingi' => ['Декинги', 'Импрегнирани дъски за тераси и външни настилки.'],
                ],
                'products' => [
                    ['gredi', 'Греда иглолистна C24', 'Сортирана по якост греда за покривни и подови конструкции, сушена в камера.', 2600, false],
                    ['gredi', 'Греда масивна C16', 'Конструкционна греда за по-леки натоварвания и вътрешни скари.', 1950, false],
                    ['letvi', 'Летва скара', 'Летва за скара под дюшеме, ламперия и подконструкции.', 480, false],
                    ['letvi', 'Летва покривна', 'Импрегнирана покривна летва за керемиди и мембрана.', 620, false],
                    ['daski', 'Дъска рендосана', 'Гладко рендосана дъска от планински бор, сушена до 12% влага.', 1850, false],
                    ['daski', 'Дъска необрязана', 'Бичена дъска с естествен кант, за груб строеж и обшивки.', 1150, false],
                    ['lamperia', 'Ламперия класик', 'Класически профил за стени и тавани, готов за лакиране.', 1450, false],
                    ['lamperia', 'Ламперия софт-лайн', 'Мек заоблен профил с плавен фугов детайл.', 1690, false],
                    ['pervazi', 'Первази подови', 'Масивен подов перваз, профилиран по поръчка.', 890, false],
                    ['pervazi', 'Кант ъглов', 'Ъглов кант за завършване на обшивки и каси.', 540, false],
                    ['mebeli', 'Маса масив бор', 'Маса от масивен бор, сглобена в работилницата, по размер на клиента.', 48000, false],
                    ['mebeli', 'Пейка масив', 'Пейка от масивна дървесина, в комплект или самостоятелно.', 26000, false],
                    ['dekingi', 'Декинг импрегниран', 'Импрегнирана дъска за тераси, клас на употреба UC3.', 2200, false],
                    ['dekingi', 'Подконструкция декинг', 'Носеща подконструкция за декинг, импрегнирана UC4.', 1400, false],
                ],
                'collection' => ['nova-produkciya', 'Нова продукция', 'Последното от бичкийницата.'],
                'about' => [
                    'enabled' => true,
                    'heading' => 'Три поколения в Родопите',
                    'intro' => 'Започнахме с една гатер-банцигова машина и един камион. Днес режем, сушим и импрегнираме на същото място.',
                    'founded_year' => '1974',
                    'story' => "Дъскорезница „Санкеви“ е основана през 1974 г. от Атанас Санкев, който изкупува стар гатер и започва да реже греди за околните села.\n\nПрез 1998 г. синът му Петър добавя сушилна камера — първата в района — което позволява да се предлага дюшеме с гарантирана влажност вместо сурова дървесина.\n\nДнес третото поколение управлява предприятието. Добиваме от сертифицирани горски стопанства в радиус от 40 км, режем по поръчка и доставяме до обект в цяла Южна България.",
                    'milestones' => [
                        ['year' => '1974', 'title' => 'Първият гатер', 'text' => 'Атанас Санкев започва да реже греди за околните села.'],
                        ['year' => '1998', 'title' => 'Сушилна камера', 'text' => 'Първата в района — дюшеме с контролирана влажност.'],
                        ['year' => '2011', 'title' => 'Импрегнация', 'text' => 'Собствена автоклавна инсталация за UC3 и UC4.'],
                        ['year' => '2023', 'title' => 'Разкрой по поръчка', 'text' => 'CNC разкрой по спецификация на клиента.'],
                    ],
                    'stats' => [
                        ['value' => '50+', 'label' => 'години на едно място'],
                        ['value' => '40 км', 'label' => 'радиус на добив'],
                        ['value' => '12%', 'label' => 'влажност след сушене'],
                        ['value' => '3', 'label' => 'поколения'],
                    ],
                ],
                'contact' => [
                    'heading' => 'Елате в бичкийницата',
                    'intro' => 'Обадете се предварително за разкрой и количества — ще подготвим спецификацията преди да дойдете.',
                    // Laid out street-first, the way a Bulgarian postal address
                    // is read; the owner supplied it country-first in prose.
                    // NOTE: no postcode — none was given, and guessing one onto a
                    // real business's address is worse than omitting it.
                    'address' => "Дъскорезница Санкеви\nул. „Даскал Георги Чолаков“ 17 А\nгр. Велинград, България",
                    'phone' => '+359 301 6 22 40',
                    'hours' => "Пон–Пет  07:30–17:30\nСъб  08:00–13:00\nНед  почивен",
                ],
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
