<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Package one tenant's SHOP SETUP into a single portable file.
 *
 * WHY THIS EXISTS RATHER THAN mysqldump: production is MySQL and a developer's
 * machine is SQLite, so a dump is not loadable at the other end without hand
 * editing the dialect. This writes plain JSON, which neither engine has an
 * opinion about, plus the uploaded files — which cannot come out of a dump at
 * all, because they live on disk and are gitignored.
 *
 * WHAT IT TAKES: the tenant, its store (theme settings, about, contact, nav,
 * shipping, currencies — all of it), categories, collections, products with
 * their images, option axes, values and variants, and discounts.
 *
 * WHAT IT DELIBERATELY LEAVES: orders, customers and store messages, because
 * they are other people's personal data and a copy on a laptop is a copy that
 * has to be looked after. Users, roles and subscriptions too — those are
 * platform-side, and importing them would fight whatever the target already
 * has. Pass --with-orders if you genuinely need the order history and have
 * somewhere safe to keep it.
 *
 * Files are collected BY DATABASE VALUE, never by directory: uploads are not
 * namespaced per tenant (every merchant's images share products/, categories/,
 * logos/ …), so copying a folder would either miss files or take someone
 * else's.
 *
 *   php artisan ganvo:tenant-export sankevi
 *   php artisan ganvo:tenant-export sankevi --path=/tmp/sankevi.zip --with-orders
 */
class ExportTenant extends Command
{
    protected $signature = 'ganvo:tenant-export
                            {slug : the tenant slug, e.g. sankevi}
                            {--path= : where to write the .zip (default storage/app/exports/<slug>-<date>.zip)}
                            {--with-orders : include orders, customers and messages (personal data)}';

    protected $description = "Package a tenant's shop setup and uploads into one portable .zip";

    /**
     * The graph, parents first. Each entry says how rows are found: either by
     * tenant_id, or by a foreign key into a table already collected.
     *
     * Order matters — the importer replays it top to bottom, and a child cannot
     * be remapped before its parent has new ids.
     *
     * `via` names the column the parent's ids are matched on. It is spelled out
     * rather than taken from the first entry of `keys`, because a pivot's keys
     * are listed in schema order and the first of them is not the parent —
     * category_product starts with category_id, and matching product ids
     * against it silently exported nothing.
     *
     * @var array<string, array{by: string, parent?: string, via?: string, keys?: array<string, string>}>
     */
    private const GRAPH = [
        'stores' => ['by' => 'tenant'],
        'categories' => ['by' => 'tenant'],
        'collections' => ['by' => 'tenant'],
        'products' => ['by' => 'tenant'],
        'discounts' => ['by' => 'tenant'],
        'product_images' => ['by' => 'parent', 'parent' => 'products', 'via' => 'product_id', 'keys' => ['product_id' => 'products']],
        'product_options' => ['by' => 'parent', 'parent' => 'products', 'via' => 'product_id', 'keys' => ['product_id' => 'products']],
        'product_option_values' => ['by' => 'parent', 'parent' => 'product_options', 'via' => 'product_option_id', 'keys' => ['product_option_id' => 'product_options']],
        'product_variants' => ['by' => 'parent', 'parent' => 'products', 'via' => 'product_id', 'keys' => ['product_id' => 'products']],
        'product_variant_option_values' => ['by' => 'parent', 'parent' => 'product_variants', 'via' => 'product_variant_id', 'keys' => [
            'product_variant_id' => 'product_variants',
            'product_option_id' => 'product_options',
            'product_option_value_id' => 'product_option_values',
        ]],
        'category_product' => ['by' => 'parent', 'parent' => 'products', 'via' => 'product_id', 'keys' => [
            'category_id' => 'categories',
            'product_id' => 'products',
        ]],
        'collection_product' => ['by' => 'parent', 'parent' => 'products', 'via' => 'product_id', 'keys' => [
            'collection_id' => 'collections',
            'product_id' => 'products',
        ]],
    ];

    /** Personal / order data, appended to the graph only on --with-orders. */
    private const ORDER_GRAPH = [
        'customers' => ['by' => 'tenant'],
        'orders' => ['by' => 'tenant', 'keys' => ['customer_id' => 'customers']],
        'order_items' => ['by' => 'parent', 'parent' => 'orders', 'via' => 'order_id', 'keys' => ['order_id' => 'orders']],
        'store_messages' => ['by' => 'tenant'],
    ];

    /** Columns whose value is a path on the public disk. */
    private const FILE_COLUMNS = [
        'stores' => ['logo_path', 'admin_logo_path'],
        'products' => ['image_path'],
        'product_images' => ['path'],
        'categories' => ['image_path'],
        'collections' => ['banner_path'],
    ];

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            $this->error("No tenant with slug \"{$slug}\".");

            return self::FAILURE;
        }

        $graph = self::GRAPH;
        if ($this->option('with-orders')) {
            $graph = array_merge($graph, self::ORDER_GRAPH);
        }

        $bundle = [
            'format' => 1,
            'exported_at' => now()->toIso8601String(),
            'tenant' => (array) DB::table('tenants')->where('id', $tenant->id)->first(),
            'tables' => [],
        ];

        // Ids collected per table, so a child can be found by its parent's ids.
        $ids = [];
        $files = [];

        foreach ($graph as $table => $spec) {
            $rows = $spec['by'] === 'tenant'
                ? DB::table($table)->where('tenant_id', $tenant->id)->get()
                : DB::table($table)->whereIn($spec['via'], $ids[$spec['parent']] ?? [])->get();

            $rows = $rows->map(fn ($r): array => (array) $r)->values()->all();

            $ids[$table] = array_column($rows, 'id');
            $bundle['tables'][$table] = $rows;

            foreach (self::FILE_COLUMNS[$table] ?? [] as $column) {
                foreach ($rows as $row) {
                    if (filled($row[$column] ?? null)) {
                        $files[] = $row[$column];
                    }
                }
            }

            $this->line(sprintf('  %-32s %d', $table, count($rows)));
        }

        // Images referenced from inside the store's JSON columns — the theme's
        // image slots and the about page's gallery. A dump of the column would
        // carry the paths and leave the files behind.
        foreach ($bundle['tables']['stores'] ?? [] as $store) {
            $files = array_merge($files, $this->pathsInJson($store['theme_settings'] ?? null));
            $files = array_merge($files, $this->pathsInJson($store['about'] ?? null));
            $files = array_merge($files, $this->pathsInJson($store['hero_banner'] ?? null));
        }

        $files = array_values(array_unique(array_filter($files)));

        $path = (string) ($this->option('path')
            ?: storage_path("app/exports/{$slug}-".now()->format('Y-m-d').'.zip'));

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Could not write {$path}");

            return self::FAILURE;
        }

        $zip->addFromString('bundle.json', json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $disk = Storage::disk('public');
        $copied = 0;
        $missing = [];

        foreach ($files as $file) {
            if (! $disk->exists($file)) {
                $missing[] = $file;

                continue;
            }
            $zip->addFromString('files/'.$file, $disk->get($file));
            $copied++;
        }

        $zip->close();

        $this->newLine();
        $this->info(sprintf('Wrote %s (%s)', $path, $this->humanBytes(filesize($path) ?: 0)));
        $this->line("  files copied: {$copied}");

        if ($missing !== []) {
            // Named rather than counted: a missing upload is a row pointing at
            // something that is not there, and the import cannot invent it.
            $this->warn('  referenced but not on disk: '.count($missing));
            foreach (array_slice($missing, 0, 10) as $m) {
                $this->line("    {$m}");
            }
        }

        if (! $this->option('with-orders')) {
            $this->line('  orders, customers and messages were NOT included (--with-orders to add them)');
        }

        return self::SUCCESS;
    }

    /**
     * Every string inside a JSON blob that looks like a stored upload.
     *
     * Deliberately loose — it walks the whole structure rather than knowing
     * each theme's slot names, because a manifest can grow an image slot
     * without this command being told about it. The cost of a false positive is
     * one Storage::exists() that returns false.
     *
     * @return list<string>
     */
    private function pathsInJson(mixed $json): array
    {
        if (blank($json)) {
            return [];
        }

        $data = is_string($json) ? json_decode($json, true) : $json;
        if (! is_array($data)) {
            return [];
        }

        $out = [];
        array_walk_recursive($data, function ($value) use (&$out): void {
            if (! is_string($value) || $value === '') {
                return;
            }
            // A stored path: relative, has an image-ish extension, no scheme.
            if (preg_match('#^[\w./\-]+\.(webp|jpe?g|png|gif|svg|avif)$#i', $value)
                && ! str_starts_with($value, '/')) {
                $out[] = $value;
            }
        });

        return $out;
    }

    private function humanBytes(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1).$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).'TB';
    }
}
