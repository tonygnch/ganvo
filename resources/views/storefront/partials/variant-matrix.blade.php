@php
    /*
     | Multi-axis variant picker — one chip row per option ("Дължина", "Ширина").
     |
     | Which combinations exist is not a rule anyone configures: it is simply
     | which variants the merchant created. So availability is a lookup, never a
     | rule engine — a value is offered when SOME variant carries it while
     | agreeing with what the shopper already picked on the axes BEFORE it.
     |
     | Only earlier axes constrain, which is what keeps every combination
     | reachable: the first axis is always fully open, and changing it clears a
     | now-impossible later pick instead of dead-ending the shopper. A value
     | that no variant backs is rendered disabled + struck through rather than
     | dropped, so "10 см exists, just not at 200 см" is legible.
     |
     | Rendered by partials/variant-picker, which owns the shared .vp CSS and
     | the data-vp-* contract. Expects $matrix from Product::optionMatrix().
     */
    $options = $matrix['options'];
    $combos = $matrix['combos'];
    $meta = $matrix['variants'];

    // Same fallback chain as the @money directive — we need the formatted
    // string inside a JSON payload here, not echoed into the page.
    $rate = $displayRate ?? 1.0;
    $currency = $displayCurrency ?? (isset($store) ? $store->currency : 'EUR');
    $money = fn (int $cents): string => \App\Services\Money::display($cents, $rate, $currency);

    // NO-JS FALLBACK: settle on a real, in-stock combination server-side so the
    // form always posts a variant the cart accepts even if the script below
    // never runs. When nothing at all is buyable we deliberately pre-select
    // NOTHING — a no-JS shopper must not be able to post a sold-out variant.
    $default = null;
    foreach ($combos as $variantId => $pairs) {
        if (($meta[$variantId]['stock'] ?? 0) > 0) {
            $default = $variantId;
            break;
        }
    }
    $selection = $default !== null ? $combos[$default] : [];
    $chosen = $default !== null ? $meta[$default] : null;

    // Prices are formatted server-side (currency + FX are request state the
    // browser has no access to), so the JS only ever swaps ready strings.
    $payload = [
        'options' => $options,
        'combos' => $combos,
        'variants' => collect($meta)->map(fn (array $v): array => [
            'label' => $v['label'],
            'stock' => (int) $v['stock'],
            'price' => $money((int) $v['price_cents']),
        ])->all(),
    ];

    // Mirror of stateFor() in the script below, so the first paint is already
    // correct for shoppers who never get the JS.
    $stateFor = function (int $axis, int $valueId) use ($options, $combos, $meta, $selection): string {
        $optionId = $options[$axis]['id'];
        $exists = false;
        foreach ($combos as $variantId => $pairs) {
            if (($pairs[$optionId] ?? null) !== $valueId) {
                continue;
            }
            for ($j = 0; $j < $axis; $j++) {
                $earlier = $options[$j]['id'];
                $picked = $selection[$earlier] ?? null;
                if ($picked !== null && ($pairs[$earlier] ?? null) !== $picked) {
                    continue 2;
                }
            }
            $exists = true;
            if (($meta[$variantId]['stock'] ?? 0) > 0) {
                return 'ok';
            }
        }

        return $exists ? 'soldout' : 'none';
    };
@endphp

{{-- data-vp-matrix is single-quoted on purpose: @json only escapes quotes
     INSIDE strings, so JSON's own double quotes would close a "…" attribute. --}}
<div class="vp-mx"
     data-vp-mx
     data-vp-matrix='@json($payload)'
     data-vp-unavailable="{{ __('site.storefront.product.option_unavailable') }}"
     data-vp-soldout="{{ __('site.storefront.product.out_of_stock') }}">

    {{-- The single field the rest of the platform reads (cart, sticky bar,
         price hooks). Empty value == nothing purchasable resolved yet. --}}
    <input type="hidden"
           name="variant_id"
           value="{{ $default }}"
           data-price-cents="{{ $chosen ? (int) $chosen['price_cents'] : '' }}"
           data-price-formatted="{{ $chosen ? $money((int) $chosen['price_cents']) : '' }}"
           data-stock="{{ $chosen ? (int) $chosen['stock'] : 0 }}"
           data-label="{{ $chosen['label'] ?? '' }}">

    @foreach ($options as $axis => $option)
        <div class="vp-group">
            <p class="vp-label">{{ $option['name'] }}</p>
            <div class="vp-options"
                 role="radiogroup"
                 aria-label="{{ __('site.storefront.product.choose_option', ['name' => $option['name']]) }}">
                @foreach ($option['values'] as $value)
                    @php
                        $state = $stateFor((int) $axis, (int) $value['id']);
                        $usable = $state === 'ok';
                        $note = $usable ? '' : ($state === 'soldout'
                            ? __('site.storefront.product.out_of_stock')
                            : __('site.storefront.product.option_unavailable'));
                    @endphp
                    <label @class(['vp-option', 'vp-out' => ! $usable, 'vp-soldout' => $state === 'soldout'])
                           @if (! $usable) title="{{ $note }}" @endif>
                        <input type="radio"
                               name="vp_opt_{{ $option['id'] }}"
                               value="{{ $value['id'] }}"
                               data-vp-opt="{{ $option['id'] }}"
                               data-vp-val="{{ $value['id'] }}"
                               @checked(($selection[$option['id']] ?? null) === (int) $value['id'])
                               @disabled(! $usable)
                               @if (! $usable) aria-disabled="true" @endif>
                        <span class="vp-option-body">
                            <span class="vp-option-label">{{ $value['value'] }}</span>
                            {{-- Dimming is a visual signal only; the reason has to be
                                 in the a11y tree too, and the script keeps it current. --}}
                            <span class="vp-sr" data-vp-note>{{ $note }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<style>
    .vp-mx .vp-group + .vp-group { margin-top: 1.15rem; }
    .vp-mx .vp-group .vp-label { margin-bottom: .5rem; }
    /* Sold out is dimmed but NOT struck: the combination is real, it just
       can't ship today — visually distinct from one that never existed. */
    .vp-option.vp-soldout .vp-option-body { text-decoration: none; opacity: .55; }
</style>

@once
    <script>
        // Vanilla + self-contained on purpose: several themes never load the
        // storefront JS kit, so this may not assume Alpine or any helper.
        (function () {
            if (window.__vpMatrixBound) return;
            window.__vpMatrixBound = true;

            function matrixOf(box) {
                if (! box.__vpMx) box.__vpMx = JSON.parse(box.getAttribute('data-vp-matrix'));
                return box.__vpMx;
            }

            function readSelection(box, mx) {
                var sel = {};
                mx.options.forEach(function (opt) {
                    var el = box.querySelector('input[data-vp-opt="' + opt.id + '"]:checked');
                    sel[opt.id] = el ? parseInt(el.value, 10) : null;
                });
                return sel;
            }

            // 'ok' | 'soldout' | 'none' — the PHP twin of this lives at the top
            // of this file. Only the axes BEFORE `axis` constrain the answer.
            function stateFor(mx, axis, valueId, sel) {
                var optionId = mx.options[axis].id;
                var ids = Object.keys(mx.combos);
                var exists = false;

                for (var k = 0; k < ids.length; k++) {
                    var pairs = mx.combos[ids[k]];
                    if (pairs[optionId] !== valueId) continue;

                    var agrees = true;
                    for (var j = 0; j < axis; j++) {
                        var earlier = mx.options[j].id;
                        if (sel[earlier] !== null && pairs[earlier] !== sel[earlier]) {
                            agrees = false;
                            break;
                        }
                    }
                    if (! agrees) continue;

                    exists = true;
                    if ((mx.variants[ids[k]] || {}).stock > 0) return 'ok';
                }

                return exists ? 'soldout' : 'none';
            }

            function paint(box, mx, axis, sel, strings) {
                mx.options[axis].values.forEach(function (value) {
                    var input = box.querySelector(
                        'input[data-vp-opt="' + mx.options[axis].id + '"][data-vp-val="' + value.id + '"]'
                    );
                    if (! input) return;

                    var chip = input.closest('.vp-option');
                    var state = stateFor(mx, axis, value.id, sel);
                    var usable = state === 'ok';
                    var why = state === 'soldout' ? strings.soldout : strings.gone;

                    input.disabled = ! usable;
                    chip.classList.toggle('vp-out', ! usable);
                    chip.classList.toggle('vp-soldout', state === 'soldout');

                    if (usable) {
                        input.removeAttribute('aria-disabled');
                        chip.removeAttribute('title');
                    } else {
                        input.setAttribute('aria-disabled', 'true');
                        chip.setAttribute('title', why);
                    }

                    var note = chip.querySelector('[data-vp-note]');
                    if (note) note.textContent = usable ? '' : why;
                });
            }

            function commit(box, mx, sel) {
                var hidden = box.querySelector('input[name="variant_id"]');
                if (! hidden) return;

                // Every axis must be pinned before one variant can be named —
                // a partial selection is ambiguous, so it stays unbuyable.
                var variantId = '';
                if (mx.options.every(function (o) { return sel[o.id] !== null; })) {
                    var ids = Object.keys(mx.combos);
                    for (var k = 0; k < ids.length; k++) {
                        var pairs = mx.combos[ids[k]];
                        var hit = mx.options.every(function (o) { return pairs[o.id] === sel[o.id]; });
                        if (hit) {
                            variantId = ids[k];
                            break;
                        }
                    }
                }

                var meta = variantId ? mx.variants[variantId] : null;
                hidden.value = meta ? variantId : '';
                if (meta) {
                    hidden.setAttribute('data-price-formatted', meta.price);
                    hidden.setAttribute('data-stock', meta.stock);
                    hidden.setAttribute('data-label', meta.label);
                }

                // Ride the shared picker's own change path instead of reaching
                // across scripts: it already knows the price/stock/submit hooks.
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function refresh(box) {
                var mx = matrixOf(box);
                var sel = readSelection(box, mx);
                var strings = {
                    gone: box.getAttribute('data-vp-unavailable') || '',
                    soldout: box.getAttribute('data-vp-soldout') || ''
                };

                mx.options.forEach(function (opt, axis) {
                    paint(box, mx, axis, sel, strings);

                    // A change further up may have just killed this axis' pick.
                    // Drop it — and judge the axes after it without it — rather
                    // than leaving an impossible pair sitting on screen.
                    if (sel[opt.id] !== null && stateFor(mx, axis, sel[opt.id], sel) !== 'ok') {
                        var stale = box.querySelector('input[data-vp-opt="' + opt.id + '"]:checked');
                        if (stale) stale.checked = false;
                        sel[opt.id] = null;
                    }
                });

                commit(box, mx, sel);
            }

            document.addEventListener('change', function (e) {
                if (! e.target || ! e.target.hasAttribute || ! e.target.hasAttribute('data-vp-opt')) return;
                var box = e.target.closest('[data-vp-mx]');
                if (box) refresh(box);
            });

            // First paint: the server already chose a valid combination (that is
            // the no-JS story), this just re-runs the rule so the chips agree.
            // Deferred like the shared picker's own pass — commit() reaches the
            // submit button, which is parsed after us.
            function paintAll() {
                document.querySelectorAll('[data-vp-mx]').forEach(refresh);
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', paintAll);
            } else {
                paintAll();
            }
        })();
    </script>
@endonce
