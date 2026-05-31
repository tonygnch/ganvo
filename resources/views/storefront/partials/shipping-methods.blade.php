@php
    /*
     | Shipping method picker — radio list of operator-defined methods
     | (or built-in defaults). Themes include this on their checkout
     | form; the JS keeps the summary's shipping line + grand total in
     | sync when the customer toggles between methods.
     |
     | Inputs (set by CheckoutController):
     |   $shipping_methods_for_subtotal  pre-resolved methods with
     |                                   cost_cents computed against
     |                                   the current subtotal
     |   $default_shipping_method_id     pre-selected id
     |   $discount_cents                 amount the active discount
     |                                   takes off (currently captures
     |                                   the default-method scenario;
     |                                   the JS only refreshes shipping
     |                                   + grand, not the discount line)
     |   $total_cents                    cart subtotal (sum of items)
     |
     | The JS targets elements tagged with data-sm-shipping, data-sm-grand,
     | data-sm-shipping-row (so themes can collapse/expand the row's free
     | styling). Each radio carries pre-formatted strings as data-*
     | attributes so we don't have to do currency formatting in JS.
     */
    $defaultId = old('shipping_method', $default_shipping_method_id ?? null);
    $subtotalForJs = (int) ($total_cents ?? 0);
    $discountForJs = (int) ($discount_cents ?? 0);
@endphp

<div class="sm" data-sm-root>
    @foreach (($shipping_methods_for_subtotal ?? []) as $m)
        @php
            // Pre-compute the grand total for each method so the JS can
            // swap a server-rendered, currency-aware string instead of
            // doing formatting itself. Discount is held constant — if
            // the discount type is free_shipping it'd change with the
            // shipping cost, but those are rare; the next submit
            // re-resolves on the server anyway.
            $grandForThis = max(0, $subtotalForJs + (int) $m['cost_cents'] - $discountForJs);

            // Format the money strings here in PHP rather than via the
            // @money Blade directive inside the data-* attributes: a
            // directive glued after @else (data-cost-text) does NOT get
            // compiled, which leaked a literal "@money(...)" into the
            // attribute and then into the summary when the method was
            // picked. Plain {{ }} echoes of these vars are safe everywhere.
            $smRate = $displayRate ?? 1.0;
            $smCurrency = $displayCurrency ?? (isset($store) ? $store->currency : 'EUR');
            $costText = ((int) $m['cost_cents'] === 0)
                ? __('site.common.free')
                : \App\Services\Money::display((int) $m['cost_cents'], $smRate, $smCurrency);
            $grandText = \App\Services\Money::display((int) $grandForThis, $smRate, $smCurrency);
        @endphp
        <label class="sm-option">
            <input type="radio"
                   name="shipping_method"
                   value="{{ $m['id'] }}"
                   data-cost-cents="{{ $m['cost_cents'] }}"
                   data-cost-text="{{ $costText }}"
                   data-grand-text="{{ $grandText }}"
                   data-row-free="{{ $m['cost_cents'] === 0 ? '1' : '0' }}"
                   @checked($defaultId === $m['id'])>
            <span class="sm-body">
                <span class="sm-row">
                    <span class="sm-label">{{ $m['label'] }}</span>
                    <span class="sm-cost">{{ $costText }}</span>
                </span>
                @if ($m['description'])
                    <span class="sm-desc">{{ $m['description'] }}</span>
                @endif
            </span>
        </label>
    @endforeach
</div>

<style>
    .sm { display: flex; flex-direction: column; gap: .5rem; }
    .sm-option {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .875rem 1rem;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 10px;
        background: white;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }
    .sm-option:hover { border-color: rgba(0, 0, 0, .35); }
    .sm-option input { width: 18px; height: 18px; accent-color: #111; flex-shrink: 0; }
    .sm-option:has(input:checked) {
        border-color: #111;
        background: rgba(0, 0, 0, .03);
    }
    .sm-body { flex: 1; display: flex; flex-direction: column; gap: .25rem; min-width: 0; }
    .sm-row { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
    .sm-label { font-weight: 600; font-size: .9375rem; }
    .sm-cost { font-size: .9375rem; font-variant-numeric: tabular-nums; }
    .sm-desc { color: rgba(0, 0, 0, .55); font-size: .8125rem; }
</style>

@once
    <script>
        // One-time global wiring; safe to bind once via event delegation.
        (function () {
            if (window.__smBound) return;
            window.__smBound = true;

            document.addEventListener('change', function (e) {
                if (! (e.target && e.target.matches('[data-sm-root] input[name="shipping_method"]'))) {
                    return;
                }
                var radio = e.target;
                var cost = radio.getAttribute('data-cost-text');
                var grand = radio.getAttribute('data-grand-text');
                var isFree = radio.getAttribute('data-row-free') === '1';
                // Route through the shared number-animation engine when the
                // theme has included it (storefront/partials/number-anim);
                // otherwise just set the text. Keeps the shipping/total swap
                // consistent with the cart's rolling/odometer/etc. style.
                var setNum = window.ganvoAnimateNumber || function (el, str) { el.textContent = str; };
                document.querySelectorAll('[data-sm-shipping]').forEach(function (el) { setNum(el, cost); });
                document.querySelectorAll('[data-sm-grand]').forEach(function (el) { setNum(el, grand); });
                document.querySelectorAll('[data-sm-shipping-row]').forEach(function (el) {
                    el.classList.toggle('free', isFree);
                });
            });
        })();
    </script>
@endonce
