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
        .vp { margin: 0 0 1.5rem; }
        .vp-label {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(0, 0, 0, .55);
            margin: 0 0 .625rem;
            font-weight: 600;
        }
        .vp-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: .5rem;
        }
        .vp-option {
            position: relative;
            display: block;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 10px;
            padding: .75rem .875rem;
            cursor: pointer;
            background: white;
            transition: border-color .15s ease, background-color .15s ease, transform .12s ease;
        }
        .vp-option:hover { border-color: rgba(0, 0, 0, .45); transform: translateY(-1px); }
        .vp-option input { position: absolute; opacity: 0; pointer-events: none; }
        .vp-option:has(input:checked) {
            border-color: #111;
            background: #111;
            color: white;
        }
        .vp-option-body { display: flex; flex-direction: column; gap: .125rem; }
        .vp-option-label { font-weight: 600; font-size: .9375rem; }
        .vp-option-price { font-size: .8125rem; font-variant-numeric: tabular-nums; opacity: .9; }
        .vp-option-meta {
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            opacity: .75;
            margin-top: .125rem;
        }
        .vp-option.vp-out {
            opacity: .45;
            cursor: not-allowed;
            background: rgba(0, 0, 0, .03);
        }
        .vp-option.vp-out:hover { transform: none; border-color: rgba(0, 0, 0, .15); }
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
