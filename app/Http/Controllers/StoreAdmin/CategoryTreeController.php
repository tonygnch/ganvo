<?php

namespace App\Http\Controllers\StoreAdmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Plain-HTTP endpoint backing the categories drag/drop tree page.
 *
 * Originally the persistence ran through a Livewire action on the
 * page itself, but the Livewire commit + DOM morph round-trip kept
 * breaking SortableJS bindings after the first drop — bindings landed
 * on detached elements, replacements weren't picked up, etc.
 * Routing the reorder through a regular controller endpoint lets the
 * tree be a fully client-owned widget: SortableJS binds once, the JS
 * fires fetch(), no Livewire morph ever runs.
 */
class CategoryTreeController extends Controller
{
    public function reorder(Request $request): JsonResponse
    {
        $tenantId = Auth::user()?->tenant_id;
        if (! $tenantId) {
            return response()->json(['ok' => false, 'reason' => 'no_tenant'], 403);
        }

        $data = $request->validate([
            'nodes'              => ['required', 'array'],
            'nodes.*.id'         => ['required', 'integer'],
            'nodes.*.parent_id'  => ['nullable', 'integer'],
            'nodes.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        // Verify ownership in a single query.
        $ids = collect($data['nodes'])->pluck('id')->map(fn ($v) => (int) $v)->all();
        $ownedIds = Category::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
        $ownedSet = array_flip($ownedIds);

        // Cycle detection — same logic as the Livewire version: walk
        // each new ancestor chain; any cycle aborts the whole batch.
        $parentMap = [];
        foreach ($data['nodes'] as $row) {
            $id = (int) $row['id'];
            if (! isset($ownedSet[$id])) {
                continue;
            }
            $parentMap[$id] = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
        }
        foreach ($parentMap as $id => $_) {
            $seen = [$id => true];
            $cursor = $parentMap[$id] ?? null;
            while ($cursor !== null) {
                if (isset($seen[$cursor])) {
                    return response()->json([
                        'ok' => false,
                        'reason' => 'cycle',
                        'message' => 'That move would create a cycle in the category tree.',
                    ], 422);
                }
                $seen[$cursor] = true;
                $cursor = $parentMap[$cursor] ?? null;
            }
        }

        DB::transaction(function () use ($data, $tenantId, $ownedSet) {
            foreach ($data['nodes'] as $row) {
                $id = (int) $row['id'];
                if (! isset($ownedSet[$id])) {
                    continue;
                }
                $parent = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
                if ($parent !== null && ! isset($ownedSet[$parent])) {
                    continue; // parent must also belong to this tenant
                }
                Category::query()
                    ->where('id', $id)
                    ->where('tenant_id', $tenantId)
                    ->update([
                        'parent_id' => $parent,
                        'sort_order' => (int) $row['sort_order'],
                    ]);
            }
        });

        return response()->json(['ok' => true]);
    }
}
