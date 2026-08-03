<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Load SANKEVI LTD's REAL shelving-system catalogue into the sankevi store.
 *
 * This is a SECOND product line, not a replacement. The store already sells
 * sawn timber under seven families (Греди, Дъски, Летви, Ламперия, Первази,
 * Декинги, Мебели); this command only ever adds to that. It creates and
 * updates rows it owns — identified by the slugs in CATEGORIES/PRODUCTS below
 * — and never reads, writes or deletes anything else the tenant has.
 *
 *   php artisan ganvo:sankevi-catalogue --dry-run
 *   php artisan ganvo:sankevi-catalogue
 *
 * SHAPE OF THE DATA. The price list is a matrix with holes in it: a 0.29 m
 * shelf is milled at three lengths, a 0.421 m shelf at exactly one. Thirty-six
 * loose products would say nothing about which dimension is which and would
 * bury the four real things the client sells. So each family is ONE product
 * with two option axes (Широчина, Дължина) and one variant per row that
 * actually exists in the list. The combinations the client does not make are
 * simply the ones with no variant — the storefront picker greys them out on
 * its own, nothing has to be configured to forbid them.
 *
 * PRICES are the client's own netto EUR figures, stored to the cent, VAT and
 * transport excluded — which each product description states in so many words.
 * The product's own price_cents is set to its CHEAPEST variant so the card's
 * "от X" reads correctly.
 *
 * TWO FIGURES IN THE CLIENT'S LIST LOOK LIKE TYPOS and are transcribed here
 * exactly as given, deliberately un-"corrected":
 *   · стелаж 0.40×2.70 and 0.40×3.00 are both 13.19
 *   · стелаж 0.50×1.50 (7.58) costs MORE than the longer 0.50×1.80 (7.56)
 * Ask the owner before touching either.
 *
 * STOCK is the one number NOT in the catalogue — the client gave none, because
 * the line is made to order. --stock sets it; the default keeps the products
 * purchasable rather than badging them "изчерпан", which would be a false
 * claim in the other direction. Set the real figure when the owner gives one.
 */
class ImportSankeviCatalogue extends Command
{
    protected $signature = 'ganvo:sankevi-catalogue
                            {--dry-run : do the whole import in a transaction, report it, then roll back}
                            {--stock=100 : stock quantity for the new products and variants (NOT from the catalogue)}';

    protected $description = "Import Sankevi's real shelving catalogue (shelves, stands, accessories) into the sankevi store, additively";

    /** The tenant this catalogue belongs to. */
    private const TENANT_SLUG = 'sankevi';

    /**
     * Where a photograph for these products may be found. First hit wins.
     *
     *   · the public disk itself — image_path is stored relative to it, so a
     *     file here is usable as-is;
     *   · public/images/sankevi/catalogue/ — the handoff directory. Files here
     *     are NOT reachable through Storage::url() (it always prefixes
     *     /storage, which maps to storage/app/public), so a file found here is
     *     copied onto the public disk under the same relative path.
     */
    private const DISK_DIR = 'sankevi/catalogue';
    private const HANDOFF_DIR = 'images/sankevi/catalogue';

    /** Extensions accepted for a catalogue photograph, best first. */
    private const IMAGE_EXTS = ['webp', 'avif', 'jpg', 'jpeg', 'png'];

    /** sort_order for the first new category; the existing seven occupy 0–6. */
    private const CATEGORY_SORT_BASE = 7;

    private bool $dry = false;

    private int $stock = 100;

    /** Human-readable log of what happened to each image lookup. */
    private array $imageLog = [];

    private function categories(): array
    {
        return [
            'raftove' => [
                'name' => 'Рафтове',
                'description' => 'Рафтове от масивна дървесина с гладка повърхност и добра носимоспособност.',
            ],
            'stelazhi' => [
                'name' => 'Стелажи',
                'description' => 'Странични рамки с предварително пробити отвори за свободно разполагане на рафтовете.',
            ],
            'aksesoari' => [
                'name' => 'Аксесоари',
                'description' => 'Щифтове и обтяжки за сглобяване и стабилност на стелажната система.',
            ],
        ];
    }

    /**
     * SHELVES — page 5 of the catalogue. width (m) => [length (m) => netto EUR].
     * Keys are strings so the client's own decimal places survive: 0.421 is
     * three digits, 0.30 keeps its trailing zero, and nothing gets rounded.
     */
    private function shelfMatrix(): array
    {
        return [
            '0.29' => ['0.77' => '4.76', '0.97' => '5.38', '1.17' => '6.46'],
            '0.39' => ['0.77' => '5.92', '0.97' => '6.95', '1.17' => '8.60'],
            '0.421' => ['0.97' => '7.34'],
            '0.49' => ['0.77' => '7.14', '0.97' => '8.65', '1.17' => '9.60'],
            '0.59' => ['0.97' => '10.07', '1.17' => '10.80'],
        ];
    }

    /** STANDS / side frames — page 6. width (m) => [length (m) => netto EUR]. */
    private function standMatrix(): array
    {
        return [
            '0.30' => ['1.50' => '6.62', '1.80' => '6.84', '2.10' => '8.04', '2.40' => '9.06', '3.00' => '12.30'],
            // 2.70 and 3.00 are both 13.19 in the client's list — transcribed as given.
            '0.40' => ['1.20' => '6.80', '1.50' => '7.00', '1.80' => '7.16', '2.10' => '8.48', '2.40' => '9.54', '2.70' => '13.19', '3.00' => '13.19'],
            // 1.50 (7.58) is dearer than the longer 1.80 (7.56) — transcribed as given.
            '0.50' => ['1.50' => '7.58', '1.80' => '7.56', '2.10' => '9.06', '2.40' => '10.06', '3.00' => '13.62'],
            '0.60' => ['1.50' => '7.54', '1.80' => '7.99', '2.10' => '9.54', '2.40' => '10.51', '3.00' => '14.50'],
        ];
    }

    /** The two accessories — page 5. Plain single-SKU products, no axes. */
    private function accessories(): array
    {
        return [
            [
                'slug' => 'shtift-10-mm',
                'name' => 'Щифт Ø 10 мм',
                'price' => '0.36',
                'description' => 'Метален щифт Ø 10 мм за опора на рафт в страничната рамка — среден за обща опора при разширяване на системата, краен за захващане в края. Цената е нето, в евро, без ДДС и без транспорт.',
                'images' => ['shtift', 'pin', 'pins'],
            ],
            [
                'slug' => 'krastata-obtiazhka-150-mm',
                'name' => 'Кръстата обтяжка L 150 мм',
                'price' => '3.50',
                'description' => 'Метална кръстата обтяжка с дължина 150 мм. Поема страничните сили и придава коравина на стелажа. Цената е нето, в евро, без ДДС и без транспорт.',
                'images' => ['krastata-obtiazhka', 'cross-brace', 'brace'],
            ],
        ];
    }

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');
        $this->stock = max(0, (int) $this->option('stock'));

        $tenant = Tenant::where('slug', self::TENANT_SLUG)->first();
        if (! $tenant) {
            $this->error("No tenant with slug '" . self::TENANT_SLUG . "'.");

            return self::FAILURE;
        }

        $currency = $tenant->store?->currency ?: 'EUR';
        if (strtoupper($currency) !== 'EUR') {
            $this->warn("Store currency is {$currency}, but the catalogue's prices are EUR. Storing them as EUR.");
        }

        $before = $this->census($tenant->id);
        $this->reportCensus('BEFORE', $before);

        // Said out loud on every run: this is the one number that is not the
        // client's. Everything else below comes off the printed price list.
        $this->line('');
        $this->warn("Stock is set to {$this->stock} on all 4 products and 34 variants.");
        $this->warn('  The catalogue gives NO stock figures — this line is made to order.');
        $this->warn('  Pass --stock=N once the owner gives a real figure.');

        DB::beginTransaction();

        try {
            $this->import($tenant->id);

            $after = $this->census($tenant->id);
            $this->reportImages();
            $this->reportCensus($this->dry ? 'WOULD BE' : 'AFTER', $after);
            $this->reportDelta($before, $after);

            if ($this->dry) {
                DB::rollBack();
                $this->line('');
                $this->info('--dry-run: transaction rolled back, nothing was changed.');
                if ($this->imageLog !== []) {
                    $this->line('  (no image files were copied either)');
                }

                return self::SUCCESS;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Rolled back: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Done. The seven timber categories and their 14 products were not touched.');

        return self::SUCCESS;
    }

    private function import(int $tenantId): void
    {
        $cats = [];
        foreach (array_values(array_keys($this->categories())) as $i => $slug) {
            $cfg = $this->categories()[$slug];
            $cats[$slug] = $this->upsertCategory($tenantId, $slug, $cfg, self::CATEGORY_SORT_BASE + $i);
        }

        $this->upsertMatrixProduct(
            tenantId: $tenantId,
            slug: 'raft',
            name: 'Рафт',
            category: $cats['raftove'],
            matrix: $this->shelfMatrix(),
            description: 'Рафт от масивна дървесина за стелажната система — гладко обработена повърхност и добра носимоспособност. '
                . 'Избираш широчина и дължина; произвеждаме само комбинациите от списъка. '
                . 'Цените са нето, в евро, без ДДС и без транспорт.',
            imageStems: ['raft', 'shelves', 'shelf', 'raft'],
        );

        $this->upsertMatrixProduct(
            tenantId: $tenantId,
            slug: 'stelazh',
            name: 'Стелаж',
            category: $cats['stelazhi'],
            matrix: $this->standMatrix(),
            description: 'Странична рамка — носещата част на стелажа. Отворите са пробити предварително, '
                . 'така че рафтовете се преместват на височина без допълнително пробиване. '
                . 'При разширяване настрани една рамка носи рафтовете и от двете си страни, '
                . 'тоест всяка следваща секция спестява по една рамка — по-малко материал, по-ниска цена, по-бърз монтаж. '
                . 'Избираш широчина и дължина; произвеждаме само комбинациите от списъка. '
                . 'Цените са нето, в евро, без ДДС и без транспорт.',
            imageStems: ['stelazh', 'stands', 'stand', 'side-frame', 'frame'],
        );

        foreach ($this->accessories() as $acc) {
            $this->upsertPlainProduct($tenantId, $acc, $cats['aksesoari']);
        }
    }

    private function upsertCategory(int $tenantId, string $slug, array $cfg, int $sort): Category
    {
        $cat = Category::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();

        if ($cat && $cat->trashed()) {
            $cat->restore();
        }
        if (! $cat) {
            $cat = new Category(['tenant_id' => $tenantId, 'slug' => $slug]);
            $cat->tenant_id = $tenantId;
            $cat->slug = $slug;
        }

        $cat->fill([
            'name' => $cfg['name'],
            'description' => $cfg['description'],
            'sort_order' => $sort,
            'is_active' => true,
            'show_in_menu' => true,
        ])->save();

        return $cat;
    }

    /**
     * One product carrying a Широчина × Дължина matrix, one variant per row
     * that really exists in the client's price list.
     */
    private function upsertMatrixProduct(
        int $tenantId,
        string $slug,
        string $name,
        Category $category,
        array $matrix,
        string $description,
        array $imageStems,
    ): void {
        // Cheapest row drives the product's own price, which is what the
        // storefront card renders as "от X".
        $cheapest = null;
        foreach ($matrix as $lengths) {
            foreach ($lengths as $price) {
                $cents = $this->cents($price);
                $cheapest = $cheapest === null ? $cents : min($cheapest, $cents);
            }
        }

        $product = $this->upsertProduct($tenantId, $slug, [
            'name' => $name,
            'description' => $description,
            'price_cents' => $cheapest,
        ], $imageStems);

        $category->products()->syncWithoutDetaching([$product->id]);

        // Axes, in the order the client's own table reads: width then length.
        $widthOpt = $this->upsertOption($product->id, 'Широчина', 0);
        $lengthOpt = $this->upsertOption($product->id, 'Дължина', 1);

        $widths = array_keys($matrix);
        usort($widths, fn ($a, $b) => (float) $a <=> (float) $b);

        $lengths = [];
        foreach ($matrix as $row) {
            foreach (array_keys($row) as $l) {
                $lengths[$l] = true;
            }
        }
        $lengths = array_keys($lengths);
        usort($lengths, fn ($a, $b) => (float) $a <=> (float) $b);

        $widthVals = [];
        foreach ($widths as $i => $w) {
            $widthVals[$w] = $this->upsertOptionValue($widthOpt->id, $this->metres($w), $i);
        }
        $lengthVals = [];
        foreach ($lengths as $i => $l) {
            $lengthVals[$l] = $this->upsertOptionValue($lengthOpt->id, $this->metres($l), $i);
        }

        // Any value left over from an earlier run with different dimensions.
        $this->pruneOptionValues($widthOpt, array_map(fn ($w) => $this->metres($w), $widths));
        $this->pruneOptionValues($lengthOpt, array_map(fn ($l) => $this->metres($l), $lengths));
        $this->pruneOptions($product->id, ['Широчина', 'Дължина']);

        $keptSkus = [];
        $sort = 0;
        foreach ($widths as $w) {
            foreach ($lengths as $l) {
                if (! isset($matrix[$w][$l])) {
                    // Not milled at this pair. No variant — that IS the rule.
                    continue;
                }

                $sku = $slug . '-' . $this->dimKey($w) . 'x' . $this->dimKey($l);
                $keptSkus[] = $sku;

                $variant = ProductVariant::firstOrNew([
                    'product_id' => $product->id,
                    'sku' => $sku,
                ]);
                $variant->fill([
                    'label' => 'ш. ' . $this->metres($w) . ' × д. ' . $this->metres($l),
                    'price_cents' => $this->cents($matrix[$w][$l]),
                    'stock_quantity' => $this->stock,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]);
                $variant->product_id = $product->id;
                $variant->sku = $sku;
                $variant->save();

                $variant->optionValues()->sync([
                    $widthVals[$w]->id => ['product_option_id' => $widthOpt->id],
                    $lengthVals[$l]->id => ['product_option_id' => $lengthOpt->id],
                ]);
            }
        }

        ProductVariant::where('product_id', $product->id)
            ->whereNotIn('sku', $keptSkus)
            ->delete();
    }

    /** An accessory: one price, no axes, no variants. */
    private function upsertPlainProduct(int $tenantId, array $cfg, Category $category): void
    {
        $product = $this->upsertProduct($tenantId, $cfg['slug'], [
            'name' => $cfg['name'],
            'description' => $cfg['description'],
            'price_cents' => $this->cents($cfg['price']),
        ], $cfg['images']);

        $category->products()->syncWithoutDetaching([$product->id]);

        // Should never have any, but keeps re-runs honest if the shape changed.
        ProductVariant::where('product_id', $product->id)->delete();
        ProductOption::where('product_id', $product->id)->delete();
    }

    private function upsertProduct(int $tenantId, string $slug, array $attrs, array $imageStems): Product
    {
        $product = Product::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();

        if ($product && $product->trashed()) {
            $product->restore();
        }
        if (! $product) {
            $product = new Product();
            $product->tenant_id = $tenantId;
            $product->slug = $slug;
        }

        $image = $this->resolveImage($slug, $imageStems);

        $product->fill($attrs);
        $product->currency = 'EUR';
        $product->stock_quantity = $this->stock;
        $product->is_active = true;
        // Never blank an image an operator has since set by hand.
        if ($image !== null) {
            $product->image_path = $image;
        }
        $product->save();

        return $product;
    }

    private function upsertOption(int $productId, string $name, int $sort): ProductOption
    {
        $opt = ProductOption::firstOrNew(['product_id' => $productId, 'name' => $name]);
        $opt->product_id = $productId;
        $opt->name = $name;
        $opt->sort_order = $sort;
        $opt->save();

        return $opt;
    }

    private function upsertOptionValue(int $optionId, string $value, int $sort): ProductOptionValue
    {
        $val = ProductOptionValue::firstOrNew(['product_option_id' => $optionId, 'value' => $value]);
        $val->product_option_id = $optionId;
        $val->value = $value;
        $val->sort_order = $sort;
        $val->save();

        return $val;
    }

    private function pruneOptionValues(ProductOption $option, array $keep): void
    {
        ProductOptionValue::where('product_option_id', $option->id)
            ->whereNotIn('value', $keep)
            ->delete();
    }

    private function pruneOptions(int $productId, array $keep): void
    {
        ProductOption::where('product_id', $productId)
            ->whereNotIn('name', $keep)
            ->delete();
    }

    /**
     * Find a photograph for this product and return a path relative to the
     * public disk, or null when there is none. Never guesses: a file has to
     * actually be on disk under one of the stems, or nothing is set.
     */
    private function resolveImage(string $slug, array $stems): ?string
    {
        $stems = array_values(array_unique(array_merge([$slug], $stems)));

        $diskDir = storage_path('app/public/' . self::DISK_DIR);
        foreach ($stems as $stem) {
            foreach (self::IMAGE_EXTS as $ext) {
                $file = "{$stem}.{$ext}";
                if (is_file("{$diskDir}/{$file}")) {
                    $this->imageLog[$slug] = "public disk: " . self::DISK_DIR . "/{$file}";

                    return self::DISK_DIR . '/' . $file;
                }
            }
        }

        $handoff = public_path(self::HANDOFF_DIR);
        if (! is_dir($handoff)) {
            $this->imageLog[$slug] = 'none — public/' . self::HANDOFF_DIR . ' does not exist';

            return null;
        }

        foreach ($stems as $stem) {
            foreach (self::IMAGE_EXTS as $ext) {
                $file = "{$stem}.{$ext}";
                $src = "{$handoff}/{$file}";
                if (! is_file($src)) {
                    continue;
                }

                // Storage::url() always prefixes /storage, which maps to
                // storage/app/public — a file left in public/images/ would
                // render as a broken URL, so it is copied onto the disk.
                if (! $this->dry) {
                    File::ensureDirectoryExists($diskDir);
                    $dst = "{$diskDir}/{$file}";
                    if (! is_file($dst) || filesize($dst) !== filesize($src) || filemtime($dst) < filemtime($src)) {
                        File::copy($src, $dst);
                    }
                }
                $this->imageLog[$slug] = "copied from public/" . self::HANDOFF_DIR . "/{$file}";

                return self::DISK_DIR . '/' . $file;
            }
        }

        $this->imageLog[$slug] = 'none — no ' . implode('|', $stems) . '.{' . implode(',', self::IMAGE_EXTS) . '} in public/' . self::HANDOFF_DIR;

        return null;
    }

    /** "0.29" => "0,29 м" — the client's own digits, Bulgarian decimal comma. */
    private function metres(string $v): string
    {
        return str_replace('.', ',', $v) . ' м';
    }

    /** "0.421" => "0421", "1.50" => "150" — for SKUs. */
    private function dimKey(string $v): string
    {
        return str_replace('.', '', $v);
    }

    /** "10.07" => 1007. Netto EUR to cents, no rounding of the client's figure. */
    private function cents(string $eur): int
    {
        return (int) round(((float) $eur) * 100);
    }

    /** A snapshot precise enough to prove the timber line was left alone. */
    private function census(int $tenantId): array
    {
        $catRows = Category::where('tenant_id', $tenantId)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'name', 'slug', 'sort_order']);

        $counts = [];
        foreach ($catRows as $c) {
            $counts[$c->slug] = [
                'id' => $c->id,
                'name' => $c->name,
                'sort' => $c->sort_order,
                'products' => $c->products()->count(),
            ];
        }

        $productIds = Product::where('tenant_id', $tenantId)->pluck('id');

        return [
            'categories' => $counts,
            'category_count' => $catRows->count(),
            'products' => $productIds->count(),
            'variants' => ProductVariant::whereIn('product_id', $productIds)->count(),
            'options' => ProductOption::whereIn('product_id', $productIds)->count(),
            'option_values' => ProductOptionValue::whereIn(
                'product_option_id',
                ProductOption::whereIn('product_id', $productIds)->pluck('id')
            )->count(),
            'pivots' => DB::table('product_variant_option_values')
                ->whereIn('product_variant_id', ProductVariant::whereIn('product_id', $productIds)->pluck('id'))
                ->count(),
        ];
    }

    private function reportCensus(string $label, array $c): void
    {
        $this->line('');
        $this->info("── {$label} ──");
        $this->line("  categories    : {$c['category_count']}");
        $this->line("  products      : {$c['products']}");
        $this->line("  variants      : {$c['variants']}");
        $this->line("  options       : {$c['options']}");
        $this->line("  option values : {$c['option_values']}");
        $this->line("  variant pins  : {$c['pivots']}");
        foreach ($c['categories'] as $slug => $row) {
            $this->line(sprintf('    #%-4d sort=%-2d %-12s %-10s %d product(s)', $row['id'], $row['sort'], $slug, $row['name'], $row['products']));
        }
    }

    private function reportDelta(array $a, array $b): void
    {
        $this->line('');
        $this->info('── DELTA ──');
        foreach (['category_count' => 'categories', 'products' => 'products', 'variants' => 'variants', 'options' => 'options', 'option_values' => 'option values', 'pivots' => 'variant pins'] as $k => $label) {
            $d = $b[$k] - $a[$k];
            $this->line(sprintf('  %-14s %+d  (%d → %d)', $label, $d, $a[$k], $b[$k]));
        }
    }

    private function reportImages(): void
    {
        $this->line('');
        $this->info('── IMAGES ──');
        foreach ($this->imageLog as $slug => $note) {
            $ok = ! str_starts_with($note, 'none');
            $line = sprintf('  %-26s %s', $slug, $note);
            $ok ? $this->line($line) : $this->warn($line);
        }
    }
}
