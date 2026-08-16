<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Load a bundle written by ganvo:tenant-export into THIS database.
 *
 * IDS ARE NOT PRESERVED, and that is the whole difficulty. Product ids are
 * global, not per-tenant, so production's product 821 and a local demo store's
 * product 821 are different things — inserting the first over the second would
 * corrupt a tenant nobody asked to touch. So every row goes in without its id,
 * the id the database hands back is recorded, and every foreign key pointing at
 * it is rewritten before its own table is inserted. The graph in ExportTenant
 * is ordered so a parent is always mapped before its children.
 *
 * REPLACES THE TENANT'S OWN DATA. Importing sankevi wipes the local sankevi
 * catalogue first, because merging two versions of the same shop by hand is
 * worse than either of them. Every other tenant in the database is untouched —
 * the wipe is scoped by tenant_id and by the parent ids underneath it.
 *
 *   php artisan ganvo:tenant-import storage/app/exports/sankevi-2026-08-15.zip
 *
 * Refuses to run in production without --force. Pulling a shop from one machine
 * onto another is a development move; doing it TO the live site would delete
 * the live catalogue and replace it with a snapshot.
 */
class ImportTenant extends Command
{
    protected $signature = 'ganvo:tenant-import
                            {bundle : path to the .zip written by ganvo:tenant-export}
                            {--force : allow this to run when the app is in production}';

    protected $description = 'Load a tenant bundle into this database, replacing that tenant';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to run in production. This REPLACES the tenant it imports.');
            $this->line('If you really mean it, re-run with --force.');

            return self::FAILURE;
        }

        $path = $this->resolveBundle((string) $this->argument('bundle'));

        if ($path === null) {
            return self::FAILURE;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->error("Could not open {$path} as a zip.");

            return self::FAILURE;
        }

        $raw = $zip->getFromName('bundle.json');
        if ($raw === false) {
            $this->error('bundle.json is missing — is this a ganvo:tenant-export file?');
            $zip->close();

            return self::FAILURE;
        }

        $bundle = json_decode($raw, true);
        if (! is_array($bundle) || ! isset($bundle['tenant']['slug'])) {
            $this->error('bundle.json is not readable.');
            $zip->close();

            return self::FAILURE;
        }

        $slug = $bundle['tenant']['slug'];
        $this->line("Importing tenant \"{$slug}\" exported {$bundle['exported_at']}");

        if (! $this->option('no-interaction') && ! $this->confirm("This REPLACES the local \"{$slug}\" shop. Continue?", true)) {
            $zip->close();

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($bundle): void {
                $tenantId = $this->upsertTenant($bundle['tenant']);
                $this->wipe($tenantId);
                $this->insert($bundle['tables'] ?? [], $tenantId);
            });
        } catch (Throwable $e) {
            $zip->close();
            $this->error('Rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        $files = $this->restoreFiles($zip);
        $zip->close();

        $this->newLine();
        $this->info("Imported \"{$slug}\".");
        $this->line("  files restored: {$files}");
        $this->line('  run `php artisan optimize:clear` if the site looks stale');

        return self::SUCCESS;
    }

    /**
     * Find the bundle, and when it is not there say what IS.
     *
     * The export stamps the date into the filename, so the path someone types
     * from memory — or copies out of a README — is usually right about the
     * directory and wrong about the name. "No such file" makes that a guessing
     * game; listing the directory ends it.
     *
     * A relative path is resolved against the project root rather than the
     * working directory, because this is normally run through `docker exec`,
     * where the working directory is not where anyone thinks it is.
     */
    private function resolveBundle(string $given): ?string
    {
        foreach ([$given, base_path($given)] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $this->error("No such file: {$given}");

        $dir = storage_path('app/exports');
        $found = is_dir($dir) ? glob($dir.'/*.zip') : [];

        if ($found === [] || $found === false) {
            $this->line("Nothing in {$dir} either — run ganvo:tenant-export first.");

            return null;
        }

        $this->newLine();
        $this->line('Bundles available:');
        foreach ($found as $file) {
            $this->line(sprintf('  %s   (%s)', $file, $this->humanBytes(filesize($file) ?: 0)));
        }

        return null;
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

    /** Match the tenant by slug — the one identifier that means the same thing on both machines. */
    private function upsertTenant(array $row): int
    {
        $existing = Tenant::where('slug', $row['slug'])->first();

        unset($row['id'], $row['created_at'], $row['updated_at']);

        if ($existing) {
            DB::table('tenants')->where('id', $existing->id)->update($row);

            return $existing->id;
        }

        return (int) DB::table('tenants')->insertGetId($row + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Clear this tenant's catalogue, children first.
     *
     * Scoped by tenant_id, and for the child tables by the parent ids that
     * belong to it — never a bare truncate, which would take every other
     * merchant on the machine with it.
     */
    private function wipe(int $tenantId): void
    {
        $productIds = DB::table('products')->where('tenant_id', $tenantId)->pluck('id');
        $optionIds = DB::table('product_options')->whereIn('product_id', $productIds)->pluck('id');
        $variantIds = DB::table('product_variants')->whereIn('product_id', $productIds)->pluck('id');
        $orderIds = DB::table('orders')->where('tenant_id', $tenantId)->pluck('id');

        DB::table('product_variant_option_values')->whereIn('product_variant_id', $variantIds)->delete();
        DB::table('product_option_values')->whereIn('product_option_id', $optionIds)->delete();
        DB::table('product_options')->whereIn('product_id', $productIds)->delete();
        DB::table('product_variants')->whereIn('product_id', $productIds)->delete();
        DB::table('product_images')->whereIn('product_id', $productIds)->delete();
        DB::table('category_product')->whereIn('product_id', $productIds)->delete();
        DB::table('collection_product')->whereIn('product_id', $productIds)->delete();
        DB::table('order_items')->whereIn('order_id', $orderIds)->delete();

        foreach (['orders', 'customers', 'store_messages', 'discounts', 'products', 'collections', 'stores'] as $table) {
            DB::table($table)->where('tenant_id', $tenantId)->delete();
        }

        // Categories self-reference, so children have to go before parents.
        DB::table('categories')->where('tenant_id', $tenantId)->whereNotNull('parent_id')->delete();
        DB::table('categories')->where('tenant_id', $tenantId)->delete();
    }

    /**
     * Replay the bundle, remapping every id as it goes.
     *
     * @param  array<string, list<array<string, mixed>>>  $tables
     */
    private function insert(array $tables, int $tenantId): void
    {
        /** @var array<string, array<int,int>> old id => new id, per table */
        $map = [];

        // Which columns point where. Kept here rather than in the bundle so an
        // old export still imports correctly after the schema grows a key.
        $fks = [
            'products' => [],
            'categories' => ['parent_id' => 'categories'],
            'product_images' => ['product_id' => 'products'],
            'product_options' => ['product_id' => 'products'],
            'product_option_values' => ['product_option_id' => 'product_options'],
            'product_variants' => ['product_id' => 'products'],
            'product_variant_option_values' => [
                'product_variant_id' => 'product_variants',
                'product_option_id' => 'product_options',
                'product_option_value_id' => 'product_option_values',
            ],
            'category_product' => ['category_id' => 'categories', 'product_id' => 'products'],
            'collection_product' => ['collection_id' => 'collections', 'product_id' => 'products'],
            'orders' => ['customer_id' => 'customers'],
            'order_items' => ['order_id' => 'orders', 'product_id' => 'products', 'product_variant_id' => 'product_variants'],
        ];

        foreach ($tables as $table => $rows) {
            if ($rows === []) {
                $this->line(sprintf('  %-32s 0', $table));

                continue;
            }

            // categories.parent_id points at a category in this same batch, so
            // it is blanked now and stitched back once every id is known.
            $selfRef = $table === 'categories';
            $deferred = [];

            foreach ($rows as $row) {
                $oldId = $row['id'] ?? null;
                unset($row['id']);

                if (array_key_exists('tenant_id', $row)) {
                    $row['tenant_id'] = $tenantId;
                }

                /*
                 | THE PREVIEW LOCK DOES NOT TRAVEL.
                 |
                 | It gates a PUBLIC site behind a password, and the credentials
                 | that open it live in the server's .env — which is not in the
                 | bundle and should never be. Copying the flag without them
                 | locks the developer out of the very shop they just pulled
                 | down: every page 401s and nothing in the bundle can undo it.
                 |
                 | A local mirror has nothing to hide from, so it arrives
                 | unlocked. Turning it back on is one toggle in the admin.
                 */
                if ($table === 'stores' && array_key_exists('preview_lock', $row)) {
                    $row['preview_lock'] = false;
                }

                foreach ($fks[$table] ?? [] as $column => $target) {
                    if (! array_key_exists($column, $row) || $row[$column] === null) {
                        continue;
                    }
                    if ($selfRef && $target === $table) {
                        $deferred[$oldId] = $row[$column];
                        $row[$column] = null;

                        continue;
                    }
                    // A key we cannot map points outside the bundle; null is
                    // honest, a stale id is not.
                    $row[$column] = $map[$target][$row[$column]] ?? null;
                }

                $newId = DB::table($table)->insertGetId($row);
                if ($oldId !== null) {
                    $map[$table][$oldId] = $newId;
                }
            }

            foreach ($deferred as $oldChild => $oldParent) {
                DB::table($table)
                    ->where('id', $map[$table][$oldChild] ?? 0)
                    ->update(['parent_id' => $map[$table][$oldParent] ?? null]);
            }

            $this->line(sprintf('  %-32s %d', $table, count($rows)));
        }
    }

    /** Unpack files/… back onto the public disk, keeping their stored paths. */
    private function restoreFiles(ZipArchive $zip): int
    {
        $disk = Storage::disk('public');
        $count = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! str_starts_with($name, 'files/')) {
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                continue;
            }

            $disk->put(substr($name, strlen('files/')), $contents);
            $count++;
        }

        return $count;
    }
}
