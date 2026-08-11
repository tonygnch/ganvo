<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * DATA migration, not a schema one. Nothing changes shape here.
 *
 * WHY A MIGRATION AT ALL
 *
 * The Sankevi content — their real About and Contact copy, the announcement
 * tape, the workshop gallery, the theme copy overrides, and the shelving
 * catalogue — lives in two idempotent artisan commands. Commands do not run
 * themselves. deploy/deploy.sh runs `artisan migrate --force` and never runs
 * `db:seed`, so a seeder alone would sit in the repo while production kept
 * serving the demo fiction: a family sawmill founded in 1974 by a
 * great-grandfather who does not exist, at an address that is not theirs.
 *
 * A migration is the one thing the deploy pipeline applies on its own, and the
 * migrations table makes it exactly once. That is what "one-time" means here.
 *
 * ORDERING ON A FRESH DATABASE
 *
 * `migrate:fresh --seed` runs every migration BEFORE any seeder, so on a new
 * database there is no `sankevi` tenant when this runs. That is expected, not
 * a failure: it no-ops, and SankeviClientSeeder applies the same content once
 * the tenant exists. The two cover opposite directions — this one an existing
 * production database, the seeder a new one.
 *
 * WHY IT GUARDS SO HEAVILY
 *
 * Calling application code from a migration is normally a mistake, because
 * migrations run against historical states of the app and the code they call
 * keeps moving. This one is deliberately unable to break a deploy: if the
 * tenant is missing, or the commands have since been renamed or deleted, or
 * either one throws, it records the reason and returns. A store that keeps its
 * old content is a content problem someone can fix by hand; a migration that
 * throws is a failed deploy at 2am.
 */
return new class extends Migration
{
    private const SLUG = 'sankevi';

    /**
     * ganvo:sankevi-catalogue used to run here too. It does not any more — see
     * the note in ImportSankeviCatalogue: the client retired the shelving range
     * and this migration would have quietly put it back on the next deploy.
     *
     * @var array<string, array<string, string>>
     */
    private const COMMANDS = [
        'ganvo:sankevi-pages' => ['--slug' => self::SLUG],
    ];

    public function up(): void
    {
        if (! Tenant::where('slug', self::SLUG)->exists()) {
            $this->note('no `'.self::SLUG.'` tenant on this database — skipped (a fresh install seeds it instead)');

            return;
        }

        $registered = array_keys(Artisan::all());

        foreach (self::COMMANDS as $command => $args) {
            if (! in_array($command, $registered, true)) {
                $this->note("`{$command}` is not registered any more — skipped");

                continue;
            }

            try {
                Artisan::call($command, $args);
                $this->note("{$command} ok");
            } catch (Throwable $e) {
                // Deliberately swallowed. See the docblock: the content is
                // recoverable by re-running the command by hand, a broken
                // deploy is not.
                $this->note("{$command} FAILED — run it by hand: ".$e->getMessage());
            }
        }
    }

    /**
     * Irreversible, and saying so is the honest answer.
     *
     * The "before" state is invented demo copy that was wrong about the client
     * in every particular. There is nothing here worth restoring, and writing
     * a down() that put the great-grandfather back would be worse than having
     * none.
     */
    public function down(): void
    {
        //
    }

    private function note(string $message): void
    {
        if (isset($this->output)) {
            $this->output->writeln("  <comment>sankevi:</comment> {$message}");
        }
    }
};
