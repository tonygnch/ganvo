<?php

namespace App\Filament\StoreAdmin\Resources\Categories\Pages;

use App\Filament\StoreAdmin\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tree view replacement for the default ListRecords page. Renders the
 * full nested category hierarchy with drag-and-drop reordering and
 * re-parenting (drop into another node = becomes its child; drop into
 * the root container = becomes a root category).
 *
 * Persists structural edits via the {@see reorder()} Livewire action
 * called from the SortableJS-driven Blade view. Each node still has
 * inline Edit + Delete buttons that route through the standard
 * Filament edit/delete flows.
 */
class ListCategories extends Page
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.store-admin.resources.categories.tree';

    // Hydrated by mount() + refreshed after every reorder so the view
    // always renders the current persisted state.
    public array $tree = [];

    public function mount(): void
    {
        $this->refreshTree();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add category')
                ->url(static::$resource::getUrl('create')),
        ];
    }

    /**
     * Livewire action called from the JS after every drop. Accepts a
     * flat list of {id, parent_id|null, sort_order} updates and
     * persists them in a single transaction. Validates each id against
     * the current tenant before touching anything — defense against
     * tampered payloads.
     *
     * @param array<int, array{id: int, parent_id: ?int, sort_order: int}> $payload
     */
    public function reorder(array $payload): void
    {
        $tenantId = auth()->user()?->tenant_id;
        if (! $tenantId || empty($payload)) {
            return;
        }

        // Pull every id mentioned in one query so we can verify
        // ownership without N round-trips.
        $ids = collect($payload)->pluck('id')->filter()->map(fn ($v) => (int) $v)->all();
        $ownedIds = Category::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
        $ownedSet = array_flip($ownedIds);

        // Allowed parent set: owned ids + null (= root). Anything else
        // gets dropped silently.
        $allowedParents = array_flip($ownedIds);
        $allowedParents[''] = true; // marker we'll check via array_key_exists

        // Detect cycles before persisting: build a {child => parent}
        // map from the payload and walk each ancestor chain. Any cycle
        // aborts the whole operation so we don't leave the tree wedged.
        $parentMap = [];
        foreach ($payload as $row) {
            $id = (int) ($row['id'] ?? 0);
            if (! isset($ownedSet[$id])) {
                continue;
            }
            $parentMap[$id] = $row['parent_id'] !== null && $row['parent_id'] !== ''
                ? (int) $row['parent_id']
                : null;
        }
        foreach ($parentMap as $id => $_) {
            $seen = [$id => true];
            $cursor = $parentMap[$id] ?? null;
            while ($cursor !== null) {
                if (isset($seen[$cursor])) {
                    Notification::make()
                        ->title('Move blocked')
                        ->body('That would create a cycle in the category tree.')
                        ->danger()
                        ->send();
                    return;
                }
                $seen[$cursor] = true;
                $cursor = $parentMap[$cursor] ?? null;
            }
        }

        DB::transaction(function () use ($payload, $tenantId, $ownedSet, $allowedParents) {
            foreach ($payload as $row) {
                $id = (int) ($row['id'] ?? 0);
                if (! isset($ownedSet[$id])) {
                    continue;
                }
                $parent = ($row['parent_id'] !== null && $row['parent_id'] !== '')
                    ? (int) $row['parent_id']
                    : null;
                // Validate parent: must be null (root) or one of the
                // tenant's own categories.
                if ($parent !== null && ! isset($allowedParents[$parent])) {
                    continue;
                }
                Category::query()
                    ->where('id', $id)
                    ->where('tenant_id', $tenantId)
                    ->update([
                        'parent_id' => $parent,
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                    ]);
            }
        });

        $this->refreshTree();

        Notification::make()
            ->title('Tree updated')
            ->success()
            ->send();
    }

    private function refreshTree(): void
    {
        $tenantId = auth()->user()?->tenant_id;
        $rows = Category::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Build a {parent_id => [children...]} index then recursively
        // nest into a serializable tree the Blade view can iterate.
        // Product counts come from one extra query so we don't N+1 in
        // the tree walk.
        $byParent = [];
        foreach ($rows as $r) {
            $byParent[(int) ($r->parent_id ?? 0)][] = $r;
        }
        $counts = Category::query()
            ->where('tenant_id', $tenantId)
            ->withCount('products')
            ->pluck('products_count', 'id');

        $this->tree = $this->nest($byParent, 0, $counts);
    }

    /**
     * Recursively turn the by-parent index into a nested array of
     * {id, name, slug, is_active, products_count, children[]} nodes
     * suitable for the Blade view to iterate.
     */
    private function nest(array $byParent, int $parentId, Collection $counts): array
    {
        $out = [];
        foreach ($byParent[$parentId] ?? [] as $row) {
            $out[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
                'is_active' => (bool) $row->is_active,
                'products_count' => (int) ($counts[$row->id] ?? 0),
                'children' => $this->nest($byParent, (int) $row->id, $counts),
                'edit_url' => static::$resource::getUrl('edit', ['record' => $row->id]),
            ];
        }
        return $out;
    }
}
