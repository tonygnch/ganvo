{{--
    Customize theme — the form beside a live preview of the merchant's own shop.

    The form alone is ~54 text boxes, and a label like "Capability 03 — text"
    does not tell a shop owner which sentence on their page it holds. So the
    storefront sits next to it: click a line in the page to jump to its field,
    or type straight into the page.

    The preview iframe is same-origin (both live on the central domain), which
    is what makes this possible at all — the page reaches into the iframe's
    document directly. Nothing here is injected into the theme templates; the
    only thing the storefront contributes is the data-gv-slot attributes that
    ThemeCustomizer emits in preview mode.
--}}
<x-filament-panels::page>
    <div style="margin-bottom: 1rem; font-size: 0.875rem; opacity: 0.75;">
        {!! __('admin.theme.text.customizing', ['theme' => '<strong>' . e($this->themeName) . '</strong>']) !!}
        {{ __('admin.theme.text.saved_per_theme') }}
    </div>

    <div class="gv-customize" x-data="gvLivePreview()">
        <div class="gv-customize__form">
            <form wire:submit="save">
                {{ $this->form }}

                <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center;">
                    @php
                        $tenant = auth()->user()->tenant;
                        $storefrontUrl = 'http://' . $tenant->slug . '.' . config('ganvo.central_domain') . ':8000/';
                    @endphp
                    <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener"
                       class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray"
                       style="text-decoration: none;">
                        {{ __('admin.shared.action.preview_storefront') }}
                    </a>
                    <x-filament::button type="submit">{{ __('admin.shared.action.save') }}</x-filament::button>
                </div>
            </form>
        </div>

        <aside class="gv-customize__preview">
            <div class="gv-preview__bar">
                <span class="gv-preview__hint" x-text="hint"></span>
                <button type="button" class="gv-preview__reload" x-on:click="reload()"
                        title="{{ __('admin.shared.action.save') }}">&#8635;</button>
            </div>
            <div class="gv-preview__frame">
                <iframe x-ref="frame" src="{{ route('store.theme.preview') }}"
                        title="{{ __('admin.shared.action.preview_storefront') }}"
                        x-on:load="wire()"></iframe>
            </div>
        </aside>
    </div>

    <style>
        .gv-customize { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
        @media (min-width: 1280px) {
            /* The preview earns the wider half — it is the thing being read. */
            .gv-customize { grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); }
            .gv-customize__preview { position: sticky; top: 5rem; }
        }
        .gv-customize__preview { border: 1px solid rgb(228 228 231); border-radius: 0.75rem; overflow: hidden; background: #fff; }
        .dark .gv-customize__preview { border-color: rgb(63 63 70); background: rgb(24 24 27); }
        .gv-preview__bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .5rem .75rem; border-bottom: 1px solid rgb(228 228 231); font-size: .75rem; }
        .dark .gv-preview__bar { border-color: rgb(63 63 70); }
        .gv-preview__hint { opacity: .7; }
        .gv-preview__reload { font-size: 1rem; line-height: 1; padding: .25rem .5rem; border-radius: .375rem; cursor: pointer; }
        .gv-preview__reload:hover { background: rgb(244 244 245); }
        .dark .gv-preview__reload:hover { background: rgb(39 39 42); }
        /* Tall enough to show a band and its neighbours, short enough to leave
           the form reachable on a laptop. */
        .gv-preview__frame { height: min(78vh, 900px); }
        .gv-preview__frame iframe { width: 100%; height: 100%; border: 0; display: block; }
        /* The field the merchant just jumped to. */
        .gv-field-flash { animation: gvFieldFlash 1.6s ease-out; border-radius: .5rem; }
        @keyframes gvFieldFlash {
            0%, 40% { box-shadow: 0 0 0 3px rgb(251 191 36 / .55); }
            100% { box-shadow: 0 0 0 3px rgb(251 191 36 / 0); }
        }
        @media (prefers-reduced-motion: reduce) { .gv-field-flash { animation: none; outline: 2px solid rgb(251 191 36); } }
    </style>

    <script>
        function gvLivePreview() {
            return {
                hint: @js(__('admin.theme.text.preview_hint')),

                /** The iframe's document, or null before it has loaded. */
                doc() {
                    try { return this.$refs.frame?.contentDocument ?? null; } catch (e) { return null; }
                },

                /**
                 * The form input backing a slot.
                 *
                 * By id, not by attribute selector: Filament gives every field
                 * `id="form.content_<slot>"`, and getElementById needs no
                 * escaping — where `[wire\:model="…"]` has both a colon and a
                 * dot to escape and silently returns null when either is wrong.
                 * The attribute lookup is kept as a fallback in case the id
                 * convention changes.
                 */
                field(slot) {
                    return document.getElementById('form.content_' + slot)
                        ?? document.querySelector('[wire\\:model="data.content_' + slot + '"]');
                },

                reload() { this.$refs.frame.contentWindow.location.reload(); },

                /**
                 * Called on every iframe load — including the ones Livewire
                 * causes — so the wiring is re-applied to the fresh document
                 * rather than assumed to survive.
                 */
                wire() {
                    const doc = this.doc();
                    if (!doc) return;

                    const style = doc.createElement('style');
                    style.textContent = `
                        [data-gv-slot] { cursor: text; outline-offset: 2px; transition: outline-color .15s ease, background-color .15s ease; outline: 1px dashed transparent; }
                        [data-gv-slot]:hover { outline-color: rgba(251,191,36,.9); background-color: rgba(251,191,36,.10); }
                        [data-gv-slot].gv-active { outline: 2px solid rgba(251,191,36,1); background-color: rgba(251,191,36,.14); }
                        [data-gv-slot][contenteditable="true"] { outline: 2px solid rgba(59,130,246,1); background-color: rgba(59,130,246,.10); }
                    `;
                    doc.head.appendChild(style);

                    doc.querySelectorAll('[data-gv-slot]').forEach((el) => {
                        const slot = el.getAttribute('data-gv-slot');

                        // Single click: take me to the field that controls this.
                        el.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            if (el.getAttribute('contenteditable') === 'true') return;
                            this.focusField(slot, el);
                        });

                        // Double click: edit it here, in place.
                        el.addEventListener('dblclick', (e) => {
                            e.preventDefault();
                            el.setAttribute('contenteditable', 'true');
                            el.focus();
                            doc.getSelection()?.selectAllChildren(el);
                        });

                        el.addEventListener('input', () => {
                            const input = this.field(slot);
                            if (!input) return;
                            // innerHTML for the slots the theme renders unescaped,
                            // so <em> typed in place survives; text for the rest.
                            input.value = slot.endsWith('_html') ? el.innerHTML.trim() : el.innerText.trim();
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        });

                        el.addEventListener('blur', () => el.removeAttribute('contenteditable'));

                        // Enter commits rather than inserting a newline.
                        el.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); el.blur(); }
                            if (e.key === 'Escape') { el.blur(); }
                        });
                    });

                    // Typing in the form updates the page as you go.
                    document.querySelectorAll('input[id^="form.content_"], textarea[id^="form.content_"]').forEach((input) => {
                        const slot = input.id.slice('form.content_'.length);
                        if (!slot) return;
                        input.addEventListener('input', () => {
                            const target = doc.querySelector('[data-gv-slot="' + CSS.escape(slot) + '"]');
                            if (!target) return;
                            if (slot.endsWith('_html')) { target.innerHTML = input.value; }
                            else { target.textContent = input.value; }
                        });
                    });
                },

                /** Scroll to the field, open the tab it lives in, and flash it. */
                focusField(slot, el) {
                    const doc = this.doc();
                    doc?.querySelectorAll('.gv-active').forEach((n) => n.classList.remove('gv-active'));
                    el?.classList.add('gv-active');

                    const input = this.field(slot);
                    if (!input) { this.hint = slot; return; }

                    // Filament hides inactive tab panels; opening the right one
                    // first is the difference between "focus" and "nothing
                    // visibly happened".
                    const panel = input.closest('[role="tabpanel"], .fi-tabs-panel');
                    if (panel && panel.hidden) {
                        const id = panel.getAttribute('id');
                        const tab = id && document.querySelector('[aria-controls="' + id + '"]');
                        tab?.click();
                    }

                    setTimeout(() => {
                        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        input.focus({ preventScroll: true });
                        const wrap = input.closest('.fi-fo-field-wrp') ?? input;
                        wrap.classList.add('gv-field-flash');
                        setTimeout(() => wrap.classList.remove('gv-field-flash'), 1700);
                    }, 60);
                },
            };
        }
    </script>
</x-filament-panels::page>
