@php
    /*
     | Variant picker — radio-button selector for purchasable variants.
     | Themes include this *inside their add-to-cart <form>*. Renders
     | nothing when the product has no variants (single-SKU product),
     | so themes can always include it unconditionally.
     |
     | Inputs:
     |   $product  the Product (must have ->activeVariants relation
     |             loadable, or already loaded)
     |
     | Behavior:
     |   - Renders radio cards for each active variant
     |   - Each card carries data-price-formatted (server-rendered
     |     @money output) + data-stock + data-label
     |   - Vanilla JS finds elements in the surrounding form/page that
     |     are tagged with data-vp-price / data-vp-stock / data-vp-submit-price
     |     and swaps their text on selection
     |   - The hidden <input name="variant_id"> gets the picked id;
     |     the submit button stays disabled until a variant is chosen
     |     (theme buttons should carry data-vp-submit)
     */
    $variants = $product->activeVariants()->orderBy('sort_order')->get();
@endphp

@if ($variants->isNotEmpty())
    <div class="vp" data-vp-root>
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
        .vp-option-label { line-height: 1; }
        /* Per-variant price + stock are available but hidden by default —
           the picker reads as a clean label chip (matching the source
           designs); the selected variant's price still updates the main
           price via the data-vp-price hook. */
        .vp-option-price, .vp-option-meta { display: none; }
        .vp-option.vp-out .vp-option-body {
            opacity: .4;
            cursor: not-allowed;
            text-decoration: line-through;
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

                function updateForm(root) {
                    var checked = root.querySelector('input[name="variant_id"]:checked');
                    var form = root.closest('form');
                    var scope = form || document;
                    var submit = scope.querySelector('[data-vp-submit]');
                    if (! checked) {
                        if (submit) submit.disabled = true;
                        return;
                    }
                    var price = checked.getAttribute('data-price-formatted');
                    var stock = parseInt(checked.getAttribute('data-stock'), 10) || 0;

                    scope.querySelectorAll('[data-vp-price]').forEach(function (el) {
                        el.textContent = price;
                    });
                    scope.querySelectorAll('[data-vp-submit-price]').forEach(function (el) {
                        el.textContent = price;
                    });
                    scope.querySelectorAll('[data-vp-stock]').forEach(function (el) {
                        el.textContent = stock > 0 ? stock : '0';
                        // Toggle a class so themes can re-style on
                        // out-of-stock without us caring about copy.
                        el.classList.toggle('vp-stock-out', stock <= 0);
                    });

                    if (submit) submit.disabled = false;
                }

                document.addEventListener('change', function (e) {
                    if (e.target && e.target.matches('[data-vp-root] input[name="variant_id"]')) {
                        var root = e.target.closest('[data-vp-root]');
                        if (root) updateForm(root);
                    }
                });

                // Initial pass: disable submits on any unselected picker
                // so the customer must actively choose.
                document.querySelectorAll('[data-vp-root]').forEach(updateForm);
            })();
        </script>
    @endonce
@endif
