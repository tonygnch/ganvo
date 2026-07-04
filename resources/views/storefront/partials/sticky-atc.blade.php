{{--
 | Sticky mobile add-to-cart bar — appears (≤760px) once the PDP's primary
 | add-to-cart button scrolls out of view; tapping it re-submits the main
 | product form (so variant selection, drawer interception and the classic
 | fallback all keep working).
 |
 | Include on a product page, after the main form:
 |   @include('storefront.partials.sticky-atc', ['product' => $product])
--}}
<div class="gv-sticky-atc" data-gv-sticky aria-hidden="true">
    <div class="gv-info">
        <div class="gv-name">{{ $product->name }}</div>
        <div class="gv-price" data-vp-sticky-price>@money($product->price_cents)</div>
    </div>
    <button type="button" class="gv-btn" data-gv-sticky-btn>{{ __('site.storefront.product.add_to_cart') }}</button>
</div>

@push('scripts')
<script>
    (function () {
        var bar = document.querySelector('[data-gv-sticky]');
        var form = document.querySelector('form[action^="/cart/add/"]');
        if (! bar || ! form) return;
        var anchor = form.querySelector('button[type="submit"]') || form;

        // Show the bar only while the real button is off-screen.
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                var visible = entries[0].isIntersecting;
                bar.classList.toggle('gv-on', ! visible);
                bar.setAttribute('aria-hidden', visible ? 'true' : 'false');
            }, { threshold: 0 }).observe(anchor);
        }

        // Mirror the variant picker's live price when present.
        var live = form.querySelector('[data-vp-submit-price]') || document.querySelector('[data-vp-price]');
        var mine = bar.querySelector('[data-vp-sticky-price]');
        if (live && mine) {
            new MutationObserver(function () { mine.textContent = live.textContent; })
                .observe(live, { childList: true, characterData: true, subtree: true });
        }

        bar.querySelector('[data-gv-sticky-btn]').addEventListener('click', function () {
            form.requestSubmit();
        });
    })();
</script>
@endpush
