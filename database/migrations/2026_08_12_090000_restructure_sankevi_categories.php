<?php

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

/**
 * DATA migration. Nothing changes shape.
 *
 * The client redefined their product families at their August meeting: six
 * timber families, and the demo catalogue plus the shelving range retired. That
 * was applied to the development store by hand, which means production — where
 * the eleven old categories still are — would keep serving Летви, Дъски,
 * Первази, Мебели, Рафтове, Стелажи and Аксесоари under a homepage that has
 * been rewritten to talk about six other things.
 *
 * Nothing else can carry it. The seeder only runs on a fresh database, and
 * deploy.sh runs `artisan migrate` and never `db:seed`.
 *
 * WHAT IT DOES NOT DO
 *
 * It does not touch products. Categories relate to products many-to-many, so
 * detaching leaves every product row, every variant, every price and every
 * order line exactly where it was — the products simply stop being filed under
 * a category that no longer exists. Twelve of them end up uncategorised on the
 * dev store and will here too; that was the client's decision to make and this
 * migration is not the place to widen it.
 *
 * DELETION IS BY AN EXPLICIT LIST, not "everything not in the keep list". A
 * merchant who has added a category of their own since should keep it rather
 * than have it swept up by a migration written before it existed.
 */
return new class extends Migration
{
    private const SLUG = 'sankevi';

    /** slug => the name it should now carry. */
    private const KEEP = [
        'gredi' => 'Греди',
        'lamperia' => 'Ламперия',
        'dekingi' => 'Декинг',
    ];

    /** slug => name, created if absent. */
    private const CREATE = [
        'chelni-daski' => 'Челни дъски',
        'plotove-ot-masiv' => 'Плотове от масив',
        'detaili-po-poruchka' => 'Детайли по поръчка',
    ];

    /**
     * Retired, by name. „Летви 2" is a CHILD of „Летви" — categories.parent_id
     * is ON DELETE RESTRICT, so a parent whose child is still present refuses
     * to go, and the order below is the order they have to be deleted in.
     */
    private const RETIRE = [
        'letvi-2', 'letvi', 'daski', 'pervazi', 'mebeli',
        'raftove', 'stelazhi', 'aksesoari',
    ];

    /** The order the client listed them in. */
    private const ORDER = [
        'gredi', 'lamperia', 'chelni-daski', 'dekingi', 'plotove-ot-masiv', 'detaili-po-poruchka',
    ];

    public function up(): void
    {
        $tenant = Tenant::where('slug', self::SLUG)->first();

        if (! $tenant) {
            $this->note('no `'.self::SLUG.'` tenant here — skipped (a fresh install seeds it instead)');

            return;
        }

        try {
            foreach (self::KEEP as $slug => $name) {
                Category::where('tenant_id', $tenant->id)->where('slug', $slug)
                    ->where('name', '!=', $name)->update(['name' => $name]);
            }

            $retired = 0;
            foreach (self::RETIRE as $slug) {
                $category = Category::withTrashed()
                    ->where('tenant_id', $tenant->id)->where('slug', $slug)->first();

                if (! $category) {
                    continue;   // already gone, or this store never had it
                }

                $category->products()->detach();
                // Belt and braces for the RESTRICT: anything still pointing at
                // this row as a parent is orphaned rather than blocking.
                Category::where('parent_id', $category->id)->update(['parent_id' => null]);
                $category->forceDelete();
                $retired++;
            }

            foreach (self::CREATE as $slug => $name) {
                Category::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => $slug],
                    ['name' => $name, 'is_active' => true, 'show_in_menu' => true, 'sort_order' => 0],
                );
            }

            foreach (self::ORDER as $i => $slug) {
                Category::where('tenant_id', $tenant->id)->where('slug', $slug)
                    ->update(['sort_order' => $i]);
            }

            $this->note("retired {$retired}, kept ".count(self::KEEP).', created '.count(self::CREATE));
        } catch (Throwable $e) {
            // A store left on its old categories is something a person can fix
            // in the admin in two minutes. A migration that throws is a failed
            // deploy.
            $this->note('FAILED — sort the categories by hand: '.$e->getMessage());
        }
    }

    /**
     * Irreversible on purpose. The rows are gone and their names were demo
     * fiction; recreating empty categories called „Мебели" and „Первази" would
     * restore the appearance of the old shop without any of its content, which
     * is worse than leaving it alone.
     */
    public function down(): void
    {
        //
    }

    private function note(string $message): void
    {
        if (isset($this->output)) {
            $this->output->writeln("  <comment>sankevi categories:</comment> {$message}");
        }
    }
};
