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
        {{-- Sticky action bar. Save/Discard light up when the operator
             has dragged something (= unsaved changes). On clean state
             both buttons are disabled to make the "nothing to save"
             state visually obvious. --}}
        <div class="ct-toolbar" data-ct-toolbar>
            <div class="ct-help">
                Drag the <span class="ct-handle ct-handle-inline" aria-hidden="true">⋮⋮</span> handle to reorder.
                Drop into another node to nest it; drop into the top list to make it a root.
            </div>
            <div class="ct-toolbar-actions">
                <span class="ct-dirty-flag" data-ct-dirty-flag hidden>Unsaved changes</span>
                <button type="button" class="ct-btn-secondary" data-ct-discard disabled>Discard</button>
                <button type="button" class="ct-btn-primary" data-ct-save disabled>Save changes</button>
            </div>
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
        .ct-toolbar {
            position: sticky;
            top: 0;
            z-index: 30;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            padding: .75rem 1rem;
            margin: 0 0 1rem;
            border: 1px solid rgba(0,0,0,.08);
            border-radius: 10px;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .dark .ct-toolbar { background: rgba(17,24,39,.85); border-color: rgba(255,255,255,.08); }
        .ct-toolbar .ct-help {
            flex: 1;
            min-width: 0;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }
        .ct-toolbar-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-shrink: 0;
        }
        .ct-dirty-flag {
            font-size: .75rem;
            font-weight: 600;
            padding: .2rem .5rem;
            border-radius: 999px;
            background: rgba(245, 158, 11, .18);
            color: #b45309;
        }
        .dark .ct-dirty-flag { background: rgba(245, 158, 11, .22); color: #fde68a; }

        .ct-btn-primary, .ct-btn-secondary {
            font-size: .8125rem;
            font-weight: 600;
            padding: .4rem .9rem;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background-color .12s ease, border-color .12s ease, color .12s ease, opacity .12s ease;
        }
        .ct-btn-primary { background: rgb(var(--primary-600, 79 70 229)); color: white; }
        .ct-btn-primary:hover:not(:disabled) { background: rgb(var(--primary-700, 67 56 202)); }
        .ct-btn-secondary { background: transparent; color: rgba(0,0,0,.7); border-color: rgba(0,0,0,.15); }
        .ct-btn-secondary:hover:not(:disabled) { background: rgba(0,0,0,.06); color: rgba(0,0,0,.9); }
        .dark .ct-btn-secondary { color: rgba(255,255,255,.75); border-color: rgba(255,255,255,.18); }
        .dark .ct-btn-secondary:hover:not(:disabled) { background: rgba(255,255,255,.08); color: rgba(255,255,255,.95); }
        .ct-btn-primary:disabled, .ct-btn-secondary:disabled { opacity: .45; cursor: not-allowed; }

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

    {{-- The tree is wrapped in wire:ignore (see above) so Livewire
         never touches it after mount. Persistence is a plain fetch()
         to /store/categories/reorder — no Livewire round-trip, no DOM
         morph, no rebinding gymnastics. SortableJS binds once and
         stays bound through any number of drags. --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            var REORDER_URL = @json(route('store.categories.reorder'));
            // CSRF token — Laravel injects a <meta name="csrf-token"> by
            // default; Filament includes it too. Fall back to scanning
            // any input[name=_token] just in case.
            function csrfToken() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) return meta.getAttribute('content');
                var input = document.querySelector('input[name="_token"]');
                return input ? input.value : '';
            }

            function init() {
                if (typeof Sortable === 'undefined') return setTimeout(init, 50);

                function walk() {
                    var payload = [];
                    document.querySelectorAll('.ct-list').forEach(function (ul) {
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

                function flash(message, ok) {
                    // Lightweight transient toast; no Livewire involved
                    // so it can't break our DOM ownership.
                    var n = document.createElement('div');
                    n.textContent = message;
                    n.style.cssText = [
                        'position:fixed', 'top:1rem', 'right:1rem', 'z-index:9999',
                        'padding:.625rem 1rem', 'border-radius:8px',
                        'font-size:.875rem', 'font-weight:600',
                        'box-shadow:0 8px 24px -8px rgba(0,0,0,.25)',
                        'background:' + (ok ? '#16a34a' : '#dc2626'),
                        'color:white', 'opacity:0', 'transition:opacity .2s ease',
                    ].join(';');
                    document.body.appendChild(n);
                    requestAnimationFrame(function () { n.style.opacity = '1'; });
                    setTimeout(function () {
                        n.style.opacity = '0';
                        setTimeout(function () { n.remove(); }, 250);
                    }, 1600);
                }

                // Dirty-state tracking. A drop just marks the tree as
                // dirty + lights up the toolbar buttons; persistence
                // only happens when the operator clicks "Save changes".
                var saveBtn = document.querySelector('[data-ct-save]');
                var discardBtn = document.querySelector('[data-ct-discard]');
                var dirtyFlag = document.querySelector('[data-ct-dirty-flag]');

                function setDirty(flag) {
                    if (saveBtn) saveBtn.disabled = ! flag;
                    if (discardBtn) discardBtn.disabled = ! flag;
                    if (dirtyFlag) dirtyFlag.hidden = ! flag;
                }

                function save() {
                    if (saveBtn && saveBtn.disabled) return;
                    var nodes = walk();
                    // Optimistic UI: block double-click while in-flight.
                    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }
                    fetch(REORDER_URL, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ nodes: nodes }),
                    }).then(function (res) {
                        return res.json().then(function (body) { return { res: res, body: body }; });
                    }).then(function (r) {
                        if (saveBtn) saveBtn.textContent = 'Save changes';
                        if (r.res.ok && r.body && r.body.ok) {
                            flash('Tree saved', true);
                            setDirty(false);
                        } else {
                            flash((r.body && r.body.message) || 'Could not save', false);
                            // Re-enable so the operator can retry.
                            setDirty(true);
                        }
                    }).catch(function () {
                        if (saveBtn) saveBtn.textContent = 'Save changes';
                        flash('Network error — try again', false);
                        setDirty(true);
                    });
                }

                function discard() {
                    // Cheapest correct revert: reload, server re-renders
                    // the persisted state. No diffing-rollback gymnastics.
                    if (! window.confirm('Discard your unsaved changes?')) return;
                    window.location.reload();
                }

                if (saveBtn) saveBtn.addEventListener('click', save);
                if (discardBtn) discardBtn.addEventListener('click', discard);

                // Warn on accidental navigation away with pending changes.
                window.addEventListener('beforeunload', function (e) {
                    if (saveBtn && ! saveBtn.disabled) {
                        // Modern browsers ignore custom messages; just
                        // setting returnValue triggers the native prompt.
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                document.querySelectorAll('.ct-list').forEach(function (ul) {
                    if (Sortable.get(ul)) return;
                    Sortable.create(ul, {
                        group: 'ct-shared',
                        handle: '.ct-handle',
                        draggable: 'li[data-id]',
                        animation: 140,
                        fallbackOnBody: true,
                        swapThreshold: 0.55,
                        emptyInsertThreshold: 12,
                        preventOnFilter: true,
                        onEnd: function () { setDirty(true); },
                    });
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
</x-filament-panels::page>
