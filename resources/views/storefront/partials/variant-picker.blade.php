@php
    /*
     | Variant picker — how a shopper says WHICH thing they're buying.
     |
     | Two modes, one contract:
     |   flat    a radio list of variants ("Small / Medium / Large") — the
     |           original behaviour, used by every product without option axes
     |   matrix  one chip row per axis (Length × Width × …) — see
     |           partials/variant-matrix. Needed because a 100 cm board comes
     |           in 10/20 cm but a 200 cm one in 20/30: the values exist, the
     |           combination may not.
     |
     | Themes include this *inside their add-to-cart <form>*. Renders nothing
     | when the product has no variants (single-SKU product), so themes can
     | always include it unconditionally.
     |
     | Inputs:
     |   $product  the Product (must have ->activeVariants relation
     |             loadable, or already loaded)
     |
     | Contract the themes, the cart, sticky-atc and the storefront JS kit all
     | depend on — DO NOT CHANGE:
     |   - an <input name="variant_id"> inside the form carries the chosen id:
     |     the checked radio in flat mode, a hidden input in matrix mode
     |   - that input carries data-price-formatted (server-rendered @money
     |     output) + data-stock + data-label
     |   - vanilla JS finds elements in the surrounding form/page tagged with
     |     data-vp-price / data-vp-stock / data-vp-submit-price and swaps their
     |     text on selection
     |   - the submit button (tagged data-vp-submit) stays disabled until a
     |     purchasable variant is chosen
     */
    $matrix = $product->hasOptionMatrix() ? $product->optionMatrix() : null;

    // Axes are only worth rendering when some variant is pinned on every one
    // of them — a half-built matrix resolves to nothing, so we fall back to
    // the flat list instead of showing chips that can never add to cart.
    $useMatrix = $matrix !== null && $matrix['options'] !== [] && $matrix['combos'] !== [];

    $variants = $useMatrix
        ? collect()
        : $product->activeVariants()->orderBy('sort_order')->get();
@endphp

@if ($useMatrix || $variants->isNotEmpty())
    <div class="vp" data-vp-root>
        @if ($useMatrix)
            @include('storefront.partials.variant-matrix', ['matrix' => $matrix])
        @else
            <p class="vp-label">{{ __('site.storefront.product.choose_variant') }}</p>
            <div class="vp-options" role="radiogroup" aria-label="{{ __('site.storefront.product.choose_variant') }}">
                @foreach ($variants as $variant)
                    @php
                        $priceCents = $variant->price_cents !== null ? (int) $variant->price_cents : (int) $product->price_cents;
                        $outOfStock = $variant->stock_quantity <= 0;
                    @endphp
                    <label class="vp-option {{ $outOfStock ? 'vp-out' : '' }}">
                        <input type="radio"
                               name="variant_id"
                               value="{{ $variant->id }}"
                               data-price-cents="{{ $priceCents }}"
                               data-price-formatted="@money($priceCents)"
                               data-stock="{{ $variant->stock_quantity }}"
                               data-label="{{ $variant->label }}"
                               data-measure="{{ $variant->measure() ?? '' }}"
                               {{ $outOfStock ? 'disabled' : '' }}>
                        <span class="vp-option-body">
                            <span class="vp-option-label">{{ $variant->label }}</span>
                            <span class="vp-option-price">@money($priceCents)</span>
                            @if ($outOfStock)
                                <span class="vp-option-meta">{{ __('site.storefront.product.out_of_stock') }}</span>
                            @elseif ($variant->stock_quantity < 10)
                                <span class="vp-option-meta">{{ __('site.storefront.product.in_stock_low', ['count' => $variant->stock_quantity]) }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        /* Theme-variable-driven so every theme's option picker takes its OWN
           palette + radius (no hardcoded colors). Each storefront layout
           defines --accent / --border / --surface / --text and may set
           --vp-radius + --vp-on-accent to tune the shape + selected fill.
           Fallback chains keep it sane for any theme / the central pages. */
        .vp { margin: 0 0 1.5rem; }
        .vp-label {
            font-size: 0.6875rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--text-muted, var(--muted, rgba(0,0,0,.55)));
            margin: 0 0 .625rem;
            font-weight: 600;
        }
        .vp-options { display: flex; flex-wrap: wrap; gap: .5rem; }
        .vp-option { position: relative; cursor: pointer; display: inline-block; }
        .vp-option input { position: absolute; opacity: 0; pointer-events: none; }
        .vp-option-body {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .7rem 1.05rem;
            border: 1.5px solid var(--border, var(--line, rgba(0,0,0,.15)));
            border-radius: var(--vp-radius, 8px);
            background: var(--surface, var(--card, #fff));
            color: var(--text, var(--ink, var(--txt, #111)));
            font-size: .875rem;
            font-weight: 600;
            transition: border-color .15s ease, color .15s ease, background-color .15s ease;
        }
        .vp-option:hover .vp-option-body { border-color: var(--accent); }
        /* Selected: themes that set --vp-on-accent get a FILLED accent chip
           (e.g. Atelier ink); the rest get a clean accent outline. */
        .vp-option input:checked + .vp-option-body {
            border-color: var(--accent);
            color: var(--vp-on-accent, var(--accent));
            background: var(--vp-fill, transparent);
        }
        /* Keyboard users get no hover and no visible box (the real radio is
           offscreen-ish), so mirror focus onto the chip or the picker is
           untabbable in practice. */
        .vp-option input:focus-visible + .vp-option-body {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        .vp-option-label { line-height: 1; }
        /* Per-variant price + stock are available but hidden by default —
           the picker reads as a clean label chip (matching the source
           designs); the selected variant's price still updates the main
           price via the data-vp-price hook. */
        .vp-option-price, .vp-option-meta { display: none; }
        /* [hidden] is a UA rule and loses to any theme that sets display on
           its price-unit suffix. This is the one place that matters, so it
           wins outright. */
        [data-vp-price-when-picked][hidden] { display: none !important; }
        .vp-option.vp-out .vp-option-body {
            opacity: .4;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        /* Screen-reader-only: the WHY behind a dimmed chip ("not available
           with your selection") must be announced, not just implied by CSS. */
        .vp-sr {
            position: absolute;
            width: 1px; height: 1px;
            padding: 0; margin: -1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }
    </style>

    @once
        <script>
            // One-time global wiring — themes may render the picker
            // multiple times on a page (unlikely) but the JS is safe
            // to bind once with event delegation.
            (function () {
                if (window.__vpBound) return;
                window.__vpBound = true;

                // The variant the shopper has settled on, or null. Flat mode
                // keeps it in the checked radio; matrix mode narrows several
                // axes down into one hidden input, empty until it resolves.
                function resolved(root) {
                    var picked = root.querySelector('input[name="variant_id"]:checked');
                    if (picked) return picked;
                    var hidden = root.querySelector('input[name="variant_id"][type="hidden"]');
                    return hidden && hidden.value ? hidden : null;
                }

                /*
                 | WHICH ELEMENTS A SELECTION IS ALLOWED TO REWRITE.
                 |
                 | This used to be root.closest('form'), and that was the bug.
                 | Every theme puts the headline price ABOVE the add-to-cart
                 | form and the button price INSIDE it, so a form-scoped query
                 | reached the button and silently missed the headline. The
                 | shopper was shown two different prices for the same board
                 | and the big one was the wrong one — on all 13 themes, in
                 | flat mode and matrix mode alike.
                 |
                 | The page is the honest scope: [data-vp-root] is emitted once
                 | per picker, no include site sits in a loop, and no listing
                 | card tags its price with data-vp-price — so every page that
                 | exists today has exactly one picker and nothing unrelated to
                 | hit.
                 |
                 | If a page ever grows a SECOND picker, "the page" stops being
                 | unambiguous, so we fall back to the form: still wrong for the
                 | headline, but wrong locally instead of two pickers
                 | overwriting each other's prices. Replace this guard with
                 | something better if that day comes — do not just widen it.
                 */
                function textScope(root) {
                    return document.querySelectorAll('[data-vp-root]').length > 1
                        ? (root.closest('form') || document)
                        : document;
                }

                // What the SERVER wrote, kept so an un-resolved selection can
                // go back to it. Matrix mode really does un-resolve: changing
                // an upper axis clears a now-impossible lower pick, and leaving
                // the last variant's price sitting under chips that name no
                // variant is the same lie in a different place.
                function baseText(el) {
                    if (el.dataset.vpBase === undefined) {
                        el.dataset.vpBase = el.textContent;
                    }
                    return el.dataset.vpBase;
                }

                function updateForm(root) {
                    var checked = resolved(root);
                    var form = root.closest('form');
                    // The submit button stays FORM-scoped on purpose. It is a
                    // control, not a readout: disabling the wrong button blocks
                    // a sale, so it must never be reached across a form
                    // boundary. Unchanged from before.
                    var submit = (form || document).querySelector('[data-vp-submit]');

                    var scope = textScope(root);
                    var prices = scope.querySelectorAll('[data-vp-price], [data-vp-submit-price]');
                    var stocks = scope.querySelectorAll('[data-vp-stock]');
                    // Shown only once a variant names a real price — see the
                    // add-to-cart button.
                    var whenPicked = scope.querySelectorAll('[data-vp-price-when-picked]');

                    if (! checked) {
                        prices.forEach(function (el) { el.textContent = baseText(el); });
                        stocks.forEach(function (el) {
                            el.textContent = baseText(el);
                            el.classList.remove('vp-stock-out');
                        });
                        whenPicked.forEach(function (el) { el.hidden = true; });
                        if (submit) submit.disabled = true;
                        return;
                    }

                    var price = checked.getAttribute('data-price-formatted');
                    var stock = parseInt(checked.getAttribute('data-stock'), 10) || 0;

                    prices.forEach(function (el) {
                        baseText(el);
                        el.textContent = price;
                    });
                    stocks.forEach(function (el) {
                        baseText(el);
                        el.textContent = stock > 0 ? stock : '0';
                        // Toggle a class so themes can re-style on
                        // out-of-stock without us caring about copy.
                        el.classList.toggle('vp-stock-out', stock <= 0);
                    });

                    whenPicked.forEach(function (el) { el.hidden = false; });

                    // Flat mode disables out-of-stock radios, so a resolved
                    // variant is always buyable there; matrix mode can land on
                    // a real-but-sold-out combination, which must not submit.
                    if (submit) submit.disabled = stock <= 0;
                }

                document.addEventListener('change', function (e) {
                    if (e.target && e.target.matches('[data-vp-root] input[name="variant_id"]')) {
                        var root = e.target.closest('[data-vp-root]');
                        if (root) updateForm(root);
                    }
                });

                // Initial pass: disable submits on any unselected picker so the
                // customer must actively choose — and enable it for the matrix
                // picker, which server-renders a valid combination. Deferred
                // until the document is ready because [data-vp-submit] and the
                // price hooks usually sit AFTER the picker in the form and are
                // simply not parsed yet at this point.
                function syncAll() {
                    document.querySelectorAll('[data-vp-root]').forEach(updateForm);
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', syncAll);
                } else {
                    syncAll();
                }
            })();
        </script>
    @endonce
@endif
