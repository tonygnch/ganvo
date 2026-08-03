<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * SANKEVI — the real client content, laid over the demo store.
 *
 *   php artisan db:seed --class=Database\\Seeders\\SankeviClientSeeder
 *
 * NewThemesDemoSeeder builds a `sankevi` tenant, but it builds a FICTION: a
 * family sawmill in the Rhodopes selling beams, decking and skirting, founded
 * by a great-grandfather who is not real. Sankevi LTD manufacture modular
 * wooden shelving in Velingrad and export it. This seeder replaces the demo
 * facts with the ones from the client's own trade catalogue.
 *
 * WHY IT DELEGATES RATHER THAN DUPLICATES
 *
 * Everything it needs already exists as two idempotent commands, and those
 * commands are what has actually been exercised against the live store. A
 * seeder holding a second copy of the same content would be a second thing to
 * keep correct, and the copy that drifts is always the one nobody runs.
 *
 *   ganvo:sankevi-pages      About + Contact + announcement + theme copy,
 *                            and it publishes the gallery photographs onto
 *                            the public disk.
 *   ganvo:sankevi-catalogue  Shelves, stands and accessories — ADDITIVE, it
 *                            does not touch the demo timber catalogue.
 *
 * Both are safe to re-run, so this seeder is too.
 *
 * RELATIONSHIP TO THE MIGRATION OF THE SAME NAME
 *
 * There is also a one-time data migration that runs these commands, because
 * deploy.sh runs `artisan migrate` and never runs seeders — the migration is
 * the only path by which this content reaches production automatically. On a
 * fresh database the ordering is the other way round: migrations run before
 * seeders, so the tenant does not exist yet, the migration no-ops, and this
 * seeder is what applies the content. The two cover the two directions and
 * neither is redundant.
 */
class SankeviClientSeeder extends Seeder
{
    private const SLUG = 'sankevi';

    public function run(): void
    {
        if (! Tenant::where('slug', self::SLUG)->exists()) {
            // Build the base store first: this seeder layers real content over
            // the demo one, it does not create a tenant from nothing.
            // seedOnly, NOT the whole seeder — that one builds five demo stores
            // and only one of them is wanted here.
            $this->command?->info('No `sankevi` tenant — building the base store first.');

            $seeder = new NewThemesDemoSeeder;
            $seeder->setContainer(app());
            $seeder->seedOnly(self::SLUG);
        }

        // Only sankevi-pages takes --slug; sankevi-catalogue is hard-wired to
        // the one store and errors on an option it does not declare.
        foreach ([
            'ganvo:sankevi-pages' => ['--slug' => self::SLUG],
            'ganvo:sankevi-catalogue' => [],
        ] as $command => $args) {
            $this->command?->info("  {$command}");
            Artisan::call($command, $args);
            $this->command?->line(rtrim(Artisan::output()));
        }
    }
}
