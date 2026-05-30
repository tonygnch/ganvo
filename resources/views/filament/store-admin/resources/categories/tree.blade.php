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

        {{-- wire:ignore is the critical bit: once Livewire mounts this
             subtree, it stops touching it. SortableJS's binding survives
             across reorder round-trips because no morph happens.

             The server-side $tree state still updates after each reorder
             (so a hard refresh shows the persisted order), but the live
             DOM is owned by the user's drag operations, not Livewire's
             diff. --}}
        <div wire:ignore>
            <ul class="ct-list ct-root" data-ct-root data-parent-id="">
                @foreach ($tree as $node)
                    @include('filament.store-admin.resources.categories._tree-node', ['node' => $node])
                @endforeach
            </ul>
        </div>
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
            /* Drag from the handle, not text — kill native text-
               selection so misclicks on the row don't drop you into a
               selection state instead of a drag attempt. */
            user-select: none;
            -webkit-user-select: none;
        }
        /* But keep the slug + name copy-friendly when there's an
           actual selection intent (the operator double-clicks). */
        .ct-row .ct-name, .ct-row .ct-slug { user-select: text; -webkit-user-select: text; }
        .ct-row:hover { border-color: rgba(0,0,0,.2); }
        .dark .ct-row { background: rgba(255,255,255,.03); border-color: rgba(255,255,255,.08); }
        .dark .ct-row:hover { border-color: rgba(255,255,255,.2); }

        /* The drag handle is a chunky, obvious button-sized hit target
           (~32×32) so it's easy to grab with mouse or trackpad. The
           character is bigger + bolder; the box gets a soft hover so
           there's a clear "grab me" affordance. touch-action:none
           keeps the browser from scrolling instead of dragging on
           touch devices. */
        .ct-handle {
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
            color: rgba(0,0,0,.55);
            font-size: 1.25rem;
            line-height: 1;
            font-weight: 700;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid transparent;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            flex-shrink: 0;
            transition: background-color .12s ease, border-color .12s ease, color .12s ease;
        }
        .ct-handle:hover {
            background: rgba(0,0,0,.06);
            border-color: rgba(0,0,0,.12);
            color: rgba(0,0,0,.85);
        }
        .ct-handle:active { cursor: grabbing; background: rgba(0,0,0,.1); }
        .dark .ct-handle { color: rgba(255,255,255,.55); }
        .dark .ct-handle:hover { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); color: rgba(255,255,255,.95); }
        .dark .ct-handle:active { background: rgba(255,255,255,.12); }
        /* Inline-in-text variant used by the help banner — small, no
           hover/touch behavior since it's not interactive there. */
        .ct-handle-inline {
            cursor: default;
            width: auto; height: auto;
            font-size: .75rem;
            padding: 1px 6px;
            border: 1px solid rgba(0,0,0,.12);
            color: inherit;
            background: transparent;
        }
        .ct-handle-inline:hover { background: transparent; border-color: rgba(0,0,0,.12); color: inherit; }
        .dark .ct-handle-inline { border-color: rgba(255,255,255,.15); }
        .dark .ct-handle-inline:hover { background: transparent; border-color: rgba(255,255,255,.15); color: inherit; }

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

    {{-- NB: Filament v5 doesn't expose an @stack('scripts'), so we
         load SortableJS + bind inline right here. Without this the
         drag binding never attaches and the browser falls back to
         selecting text on mousedown. --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            // Lazy-init: SortableJS may load after our script, so poll
            // briefly until it's there. Beats a sync include.
            function init() {
                if (typeof Sortable === 'undefined') return setTimeout(init, 50);
                var component = @this; // Filament/Livewire page instance

                function walk() {
                    // Collect every node in DOM order, tagging each with
                    // its parent_id (from the enclosing <ul>) and
                    // sort_order (its index among siblings).
                    var payload = [];
                    document.querySelectorAll('[data-ct-root], [data-ct-root] .ct-list').forEach(function (ul) {
                        if (! ul.matches('.ct-list')) return;
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
                    document.querySelectorAll('.ct-list').forEach(function (ul) {
                        // Skip lists that already have a Sortable
                        // instance — calling .create() again would
                        // stack a second instance on the same element.
                        // Sortable.get() is the canonical way to test.
                        if (Sortable.get(ul)) return;
                        Sortable.create(ul, {
                            group: 'ct-shared',
                            handle: '.ct-handle',
                            draggable: 'li[data-id]',
                            animation: 140,
                            fallbackOnBody: true,
                            swapThreshold: 0.55,
                            emptyInsertThreshold: 12,
                            forceFallback: false,
                            preventOnFilter: true,
                            onEnd: persist,
                        });
                    });
                }

                bindAll();

                // Livewire re-renders the page after every reorder, and
                // that DOM morph can REPLACE the <ul> nodes — the new
                // ones don't carry our old Sortable instance, so drags
                // after the first stop working. Re-bind aggressively:
                //
                //   1. Livewire's commit hook (fires when a Livewire
                //      action returns + DOM is updated).
                //   2. A MutationObserver as ultimate safety net — any
                //      time a new .ct-list shows up anywhere in the
                //      page, we bind it.
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('commit', function (payload) {
                        var succeed = payload && payload.succeed;
                        if (typeof succeed === 'function') {
                            // .succeed registers a callback to run after
                            // the morph has applied to the DOM.
                            succeed(function () { bindAll(); });
                        } else {
                            // Older API shape — still try.
                            setTimeout(bindAll, 0);
                        }
                    });
                    // morph.updated also covers element replacements
                    // mid-commit (Livewire fires both per-element and
                    // per-commit hooks; defense in depth).
                    window.Livewire.hook('morph.updated', function () { bindAll(); });
                }

                // Catch-all: observe the page root for any added
                // .ct-list nodes (covers Livewire morphs we missed,
                // future Alpine/Filament re-renders, anything else).
                var rootEl = document.querySelector('[data-ct-root]')?.parentNode || document.body;
                var observer = new MutationObserver(function (mutations) {
                    var needsBind = false;
                    for (var i = 0; i < mutations.length; i++) {
                        var added = mutations[i].addedNodes;
                        for (var j = 0; j < added.length; j++) {
                            var n = added[j];
                            if (n.nodeType !== 1) continue;
                            if (n.matches && (n.matches('.ct-list') || n.querySelector('.ct-list'))) {
                                needsBind = true;
                                break;
                            }
                        }
                        if (needsBind) break;
                    }
                    if (needsBind) bindAll();
                });
                observer.observe(rootEl, { childList: true, subtree: true });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
</x-filament-panels::page>
