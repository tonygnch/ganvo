<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Database\Seeders\NewThemesDemoSeeder;
use Illuminate\Console\Command;

/**
 * Fill an EXISTING tenant with one of the demo storefronts' content.
 *
 * Why this exists rather than `db:seed --class=NewThemesDemoSeeder`:
 *
 *   1. That seeder builds ALL the demo stores. Here you name one.
 *   2. It creates owner@<slug>.test and shopper@<slug>.test, both with the
 *      password literally "password". Seeding it onto a public site plants a
 *      known-credential store admin. This command never touches accounts —
 *      the real owner already has one from onboarding.
 *   3. It calls forceDelete() on every product, category and collection the
 *      tenant owns, with no warning. Here that is refused unless the store is
 *      already empty, or you pass --force having been told exactly what goes.
 *
 * The tenant must already exist; this will not create one. Create the client
 * through the normal onboarding flow, then point this at their slug.
 *
 *   php artisan ganvo:provision-demo sankevi --dry-run
 *   php artisan ganvo:provision-demo sankevi
 *   php artisan ganvo:provision-demo sankevi --force
 *
 * NOTE ON CONTENT: the demo copy is PLACEHOLDER. For Sankevi only the company
 * name, the Velingrad address and the seven category names came from the
 * owner; the founder, the milestone years, all fourteen products and every
 * price and dimension are invented. Do not present it as the client's own
 * data without saying so.
 */
class ProvisionDemoStore extends Command
{
    protected $signature = 'ganvo:provision-demo
                            {slug : the EXISTING tenant slug to fill}
                            {--force : replace the catalogue even if the store already has one}
                            {--dry-run : report what would change and exit}';

    protected $description = 'Fill an existing tenant with a demo storefront\'s content (no accounts, guarded wipe)';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $tenant = Tenant::where('slug', $slug)->first();
        if (! $tenant) {
            $this->error("No tenant with slug '{$slug}'.");
            $this->line('Create the client through onboarding first — this command fills a store, it does not create one.');
            return self::FAILURE;
        }
        if (! $tenant->store) {
            $this->error("Tenant '{$slug}' has no store row yet.");
            return self::FAILURE;
        }

        $products = Product::where('tenant_id', $tenant->id)->withTrashed()->count();
        $cats = Category::where('tenant_id', $tenant->id)->withTrashed()->count();

        $this->line('');
        $this->info("Target: {$tenant->name}  (tenant #{$tenant->id}, slug '{$slug}')");
        $this->line("  theme now      : " . ($tenant->store->theme ?: '—'));
        $this->line("  products now   : {$products}");
        $this->line("  categories now : {$cats}");
        $this->line('');

        // The destructive part, stated plainly before anything happens.
        if ($products > 0 || $cats > 0) {
            $this->warn('This store is NOT empty. Provisioning PERMANENTLY DELETES:');
            $this->warn("  · {$products} product(s) — including soft-deleted, via forceDelete()");
            $this->warn("  · {$cats} categor(y/ies), and every collection");
            $this->warn('  and OVERWRITES the store settings: theme, nav menu, hero banner,');
            $this->warn('  announcement, About page and Contact details.');
            $this->line('');

            if (! $this->option('force')) {
                $this->error('Refusing. Re-run with --force if that is genuinely what you want.');
                return self::FAILURE;
            }
            if (! $this->option('dry-run') && ! $this->confirm('Type yes to destroy the above and rebuild', false)) {
                $this->line('Aborted. Nothing was changed.');
                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('--dry-run: nothing was changed.');
            return self::SUCCESS;
        }

        $seeder = new NewThemesDemoSeeder();
        // The whole point: no owner account, no demo shopper, no "password".
        $seeder->createAccounts = false;
        $seeder->setCommand($this);

        try {
            $seeder->seedOnly($slug);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            $this->line('Known demo configs are the keys of NewThemesDemoSeeder::stores().');
            return self::FAILURE;
        }

        $tenant->refresh();
        $after = Product::where('tenant_id', $tenant->id)->count();
        $withImages = Product::where('tenant_id', $tenant->id)->whereNotNull('image_path')->count();

        $this->line('');
        $this->info("Done. {$after} product(s), {$withImages} with a photograph.");
        $this->line("  theme is now   : " . ($tenant->store->theme ?: '—'));
        $this->line('');
        $this->warn('Remember: this content is PLACEHOLDER. Tell the client which parts are');
        $this->warn('real (name, address, category names) and which are invented.');

        return self::SUCCESS;
    }
}
