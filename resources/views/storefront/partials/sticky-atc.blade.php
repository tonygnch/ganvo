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
        {{-- The chosen size, filled by the picker. Empty until one is chosen,
             and hidden while empty so the bar does not carry a blank line —
             it is the one place a shopper can buy without the picker in
             sight, so it has to say WHICH thing it would add. --}}
        <div class="gv-variant" data-vp-label></div>
        <div class="gv-price" data-vp-sticky-price>@money($product->price_cents)</div>
    </div>
    {{-- MIRRORS THE PAGE'S OWN BUTTON. A product that is not for sale online
         shows a way to get in touch instead of an add-to-cart, and the bar has
         to say the same thing: offering "add to cart" at the bottom of a page
         whose only button says "get in touch" is a contradiction the shopper
         can only resolve by scrolling back up to check. --}}
    @if ($product->isOrderable())
        <button type="button" class="gv-btn" data-gv-sticky-btn>{{ __('site.storefront.product.add_to_cart') }}</button>
    @else
        <a class="gv-btn" href="/contact">{{ __('site.storefront.product.not_orderable_cta') }}</a>
    @endif
</div>

@push('scripts')
<script>
    (function () {
        var bar = document.querySelector('[data-gv-sticky]');
        var form = document.querySelector('form[action^="/cart/add/"]');
        if (! bar) return;

        /*
         | HOIST IT TO THE BODY.
         |
         | This bar is position: fixed, and fixed means "relative to the
         | viewport" only while no ancestor has a transform, filter or
         | perspective — any of those makes that ancestor the containing block
         | instead. Themes include this inside the product column, and those
         | columns animate in: sankevi's .pinfo is left carrying
         | `transform: matrix(1, 0, 0, 1, 0, 0)` once its reveal finishes. An
         | IDENTITY transform, doing nothing visible, and enough to pin the bar
         | to the bottom of the column — 2966px down a 850px screen, sitting in
         | the page like an ordinary block instead of hovering over it.
         |
         | Reparenting is the fix rather than hunting transforms out of every
         | theme's animations, because any future wrapper could reintroduce one
         | and the failure is silent. At body level nothing can capture it.
         |
         | Safe here: the bar is display:none until this script adds .gv-on, so
         | a reader without JS never had it in the first place.
         */
        if (bar.parentElement !== document.body) {
            document.body.appendChild(bar);
        }
        /*
         | WHAT THE BAR SHADOWS. Normally the add-to-cart form, but a product
         | that is not for sale online has no form at all — it offers a way to
         | get in touch, and the bar shadows that instead. Themes mark whichever
         | it is with data-gv-atc-anchor; a form's own submit button is found
         | without one, so nothing had to change to keep working.
         */
        /*
         | TELL THE PAGE HOW TALL THIS IS.
         |
         | Fixed to the viewport means the bar sits ON the page, not in it, and
         | what it covers at the end of a scroll is the last of the footer — the
         | copyright line was reading from underneath it. The stylesheet turns
         | --gv-atc-h into a strip of reserved footer; publishing it is this
         | script's job because the height is not knowable in CSS: the variant
         | line appears once a size is chosen, and the safe-area inset is the
         | phone's to decide.
         |
         | Measured through a hidden .gv-on rather than waiting for the bar to
         | be shown, so the room is already there when the shopper arrives at
         | the bottom instead of the footer growing under them mid-scroll.
         */
        var measuring = false;
        function publishHeight() {
            if (measuring) return;
            measuring = true;
            var shown = bar.classList.contains('gv-on');
            if (! shown) { bar.style.visibility = 'hidden'; bar.classList.add('gv-on'); }
            var h = bar.offsetHeight;
            if (! shown) { bar.classList.remove('gv-on'); bar.style.visibility = ''; }
            measuring = false;
            // 0 above the breakpoint, where .gv-on does not display at all.
            document.documentElement.style.setProperty('--gv-atc-h', h + 'px');
        }

        publishHeight();
        addEventListener('resize', publishHeight);
        addEventListener('orientationchange', publishHeight);

        var label = bar.querySelector('[data-vp-label]');
        if (label) {
            // The chosen size adds a line, and a line changes the height.
            new MutationObserver(publishHeight).observe(label, { childList: true, characterData: true, subtree: true });
        }

        var anchor = (form && form.querySelector('button[type="submit"]'))
            || document.querySelector('[data-gv-atc-anchor]')
            || form;
        if (! anchor) return;

        // Show the bar only while the real button is off-screen.
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                var visible = entries[0].isIntersecting;
                bar.classList.toggle('gv-on', ! visible);
                bar.setAttribute('aria-hidden', visible ? 'true' : 'false');
            }, { threshold: 0 }).observe(anchor);
        }

        // Mirror the variant picker's live price when present.
        var live = (form && form.querySelector('[data-vp-submit-price]')) || document.querySelector('[data-vp-price]');
        var mine = bar.querySelector('[data-vp-sticky-price]');
        if (live && mine) {
            new MutationObserver(function () { mine.textContent = live.textContent; })
                .observe(live, { childList: true, characterData: true, subtree: true });
        }

        var go = bar.querySelector('[data-gv-sticky-btn]');
        if (! go || ! form) return;      // the contact link is a plain <a>, no wiring

        go.addEventListener('click', function () {
            /*
             | The page's own button is disabled until a variant resolves; this
             | one cannot be, because it is what a shopper reaches for when the
             | picker is off-screen. So it asks first: gvNeedsVariant() moves
             | them to the picker and says why, and we do not post something the
             | server would only refuse.
             */
            if (window.gvNeedsVariant && window.gvNeedsVariant()) return;
            form.requestSubmit();
        });
    })();
</script>
@endpush
