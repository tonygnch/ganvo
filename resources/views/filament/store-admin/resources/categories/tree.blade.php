@php
    /*
     | Categories tree view — server-renders the full nested hierarchy
     | as <ul> lists with drag handles. SortableJS (loaded once via
     | CDN) drives the nested drag/drop. On every drop the JS walks
     | the live DOM tree, builds a flat
     | [{id, parent_id|null, sort_order}, ...] payload, and calls the
     | Livewire `reorder` action on the page component.
     |
     | Re-parenting is supported because every <ul> shares the same
     | SortableJS group; dropping a node onto the root container's
     | top-level list makes it a root, dropping into a child list
     | gives it that parent.
     */
@endphp

<x-filament-panels::page>
    @if (empty($tree))
        <div style="padding: 3rem 1rem; text-align: center; border: 1px dashed rgba(0,0,0,.15); border-radius: 12px; color: rgba(0,0,0,.55);">
            <p style="margin: 0 0 .75rem; font-weight: 600;">No categories yet</p>
            <p style="margin: 0; font-size: .9375rem;">Add your first category to start organizing your catalog.</p>
        </div>
    @else
        <div class="ct-help">
            Drag the <span class="ct-handle ct-handle-inline" aria-hidden="true">⋮⋮</span> handle to reorder.
            Drop a node onto another to make it a child; drop into the top-level list to make it a root.
        </div>

        <ul class="ct-list ct-root" data-ct-root data-parent-id="">
            @foreach ($tree as $node)
                @include('filament.store-admin.resources.categories._tree-node', ['node' => $node])
            @endforeach
        </ul>
    @endif

    <style>
        .ct-help {
            font-size: .875rem;
            color: rgba(0,0,0,.6);
            background: rgba(0,0,0,.04);
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 10px;
            padding: .625rem .875rem;
            margin: 0 0 1rem;
        }
        .dark .ct-help { color: rgba(255,255,255,.65); background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }

        .ct-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: .375rem;
        }
        .ct-list .ct-list {
            margin: .375rem 0 0 1.5rem;
            padding-left: .875rem;
            border-left: 2px dashed rgba(0,0,0,.12);
        }
        .dark .ct-list .ct-list { border-left-color: rgba(255,255,255,.15); }

        .ct-row {
            display: flex;
            align-items: center;
            gap: .625rem;
            padding: .5rem .625rem;
            background: rgb(var(--gray-50));
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 8px;
            transition: border-color .12s ease, background-color .12s ease;
        }
        .ct-row:hover { border-color: rgba(0,0,0,.2); }
        .dark .ct-row { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }
        .dark .ct-row:hover { border-color: rgba(255,255,255,.2); }

        .ct-handle {
            cursor: grab;
            user-select: none;
            color: rgba(0,0,0,.4);
            font-size: 1rem;
            line-height: 1;
            padding: 0 .125rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        .ct-handle:active { cursor: grabbing; }
        .dark .ct-handle { color: rgba(255,255,255,.4); }
        .ct-handle-inline { padding: 0 .25rem; border: 1px solid rgba(0,0,0,.12); border-radius: 4px; font-size: .75rem; }
        .dark .ct-handle-inline { border-color: rgba(255,255,255,.15); }

        .ct-name { font-weight: 600; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ct-slug { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .75rem; color: rgba(0,0,0,.45); }
        .dark .ct-slug { color: rgba(255,255,255,.45); }

        .ct-badge {
            font-size: .6875rem;
            font-weight: 600;
            padding: .125rem .5rem;
            border-radius: 999px;
            background: rgba(0,0,0,.06);
            color: rgba(0,0,0,.65);
        }
        .ct-badge.ct-badge-on { background: rgba(34,197,94,.15); color: #15803d; }
        .ct-badge.ct-badge-off { background: rgba(239,68,68,.12); color: #b91c1c; }
        .dark .ct-badge { background: rgba(255,255,255,.06); color: rgba(255,255,255,.65); }
        .dark .ct-badge.ct-badge-on { background: rgba(34,197,94,.18); color: #4ade80; }
        .dark .ct-badge.ct-badge-off { background: rgba(239,68,68,.18); color: #fca5a5; }

        .ct-actions { display: flex; gap: .375rem; }
        .ct-btn {
            font-size: .75rem;
            padding: .25rem .625rem;
            border-radius: 6px;
            background: transparent;
            border: 1px solid rgba(0,0,0,.12);
            color: rgba(0,0,0,.7);
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
            transition: background-color .12s ease, color .12s ease;
        }
        .ct-btn:hover { background: rgba(0,0,0,.06); color: rgba(0,0,0,.9); }
        .dark .ct-btn { border-color: rgba(255,255,255,.15); color: rgba(255,255,255,.7); }
        .dark .ct-btn:hover { background: rgba(255,255,255,.08); color: rgba(255,255,255,.95); }

        /* SortableJS leaves these on the moving + placeholder elements. */
        .sortable-ghost { opacity: .4; background: rgba(99,102,241,.15) !important; }
        .sortable-chosen { box-shadow: 0 4px 14px -4px rgba(0,0,0,.2); }
        .sortable-drag { cursor: grabbing !important; }

        /* Make empty <ul> children droppable by giving them visible
           drop area when something is being dragged over. */
        .ct-list .ct-list:empty {
            min-height: 28px;
            border-left-style: dotted;
        }
    </style>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"
                crossorigin="anonymous"
                defer></script>
        <script>
            (function () {
                // Lazy-init: SortableJS may load after our script (defer),
                // so poll briefly until it's there. Beats a sync include.
                function init() {
                    if (typeof Sortable === 'undefined') return setTimeout(init, 50);
                    var component = @this; // Filament/Livewire page instance

                    function walk() {
                        // Collect every node in DOM order, tagging each with
                        // its parent_id (from the enclosing <ul>) and
                        // sort_order (its index among siblings).
                        var payload = [];
                        document.querySelectorAll('[data-ct-root] .ct-list').forEach(function (ul) {
                            var parentId = ul.getAttribute('data-parent-id') || null;
                            var idx = 0;
                            ul.querySelectorAll(':scope > li[data-id]').forEach(function (li) {
                                payload.push({
                                    id: parseInt(li.getAttribute('data-id'), 10),
                                    parent_id: parentId ? parseInt(parentId, 10) : null,
                                    sort_order: idx++,
                                });
                            });
                        });
                        return payload;
                    }

                    function persist() {
                        component.call('reorder', walk());
                    }

                    function bindAll() {
                        document.querySelectorAll('[data-ct-root] .ct-list').forEach(function (ul) {
                            if (ul.__ctBound) return;
                            ul.__ctBound = true;
                            Sortable.create(ul, {
                                group: 'ct-shared',
                                handle: '.ct-handle',
                                draggable: 'li[data-id]',
                                animation: 140,
                                fallbackOnBody: true,
                                swapThreshold: 0.55,
                                emptyInsertThreshold: 12,
                                onEnd: persist,
                            });
                        });
                    }

                    bindAll();

                    // Re-bind after Livewire morphs the DOM (after a reorder
                    // refresh) — Livewire fires 'morph.updated' per element
                    // it patches, but we just listen for the page-wide event.
                    if (window.Livewire) {
                        window.Livewire.hook('morph.updated', bindAll);
                    }
                }
                document.addEventListener('DOMContentLoaded', init);
                if (document.readyState !== 'loading') init();
            })();
        </script>
    @endpush
</x-filament-panels::page>
