@php
    $title = $product->name;
@endphp
@extends('themes.sankevi.layout')

@section('content')
    @php
        $images = $product->allImages()->take(5);
        $heroImage = $images->first()['url'] ?? null;

        $primaryCategoryId = \Illuminate\Support\Facades\DB::table('category_product')
            ->where('product_id', $product->id)
            ->value('category_id');
        $relatedQ = \App\Models\Product::query()
            ->where('tenant_id', $product->tenant_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id);
        if ($primaryCategoryId) {
            $relatedQ->whereHas('categories', fn ($q) => $q->where('categories.id', $primaryCategoryId));
        }
        $related = $relatedQ->limit(4)->get();
        if ($related->isEmpty()) {
            $related = \App\Models\Product::query()
                ->where('tenant_id', $product->tenant_id)
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->limit(4)
                ->get();
        }

        $primaryCategory = $primaryCategoryId ? \App\Models\Category::find($primaryCategoryId) : null;
    @endphp

    <style>
        /* ===== PDP — the photograph holds the left column and stays there
           while the specification scrolls past it on the right. The story is
           the picture; the numbers are the reason anyone buys. ===== */
        .pdp { position: relative; display: grid; grid-template-columns: 1.06fr .94fr; gap: 62px; padding: 34px 0 0; align-items: start; }

        .pgal { position: sticky; top: calc(var(--header-height) + 26px); }
        .pgal .main { position: relative; aspect-ratio: 4 / 5; overflow: hidden; display: grid; place-items: center; background: linear-gradient(150deg, var(--surface2), #191510); }
        .pgal .main img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .main[data-pdp-open] { cursor: zoom-in; }
        .pgal .main .zoom { position: absolute; right: 0; bottom: 0; z-index: 3; padding: 10px 16px; background: var(--bg); font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); pointer-events: none; }
        .pgal .thumbs { display: flex; gap: 12px; margin-top: 12px; }
        .pgal .thumbs .pdp-thumb { flex: 1; aspect-ratio: 1 / 1; padding: 0; border: 1px solid transparent; background: var(--surface2); overflow: hidden; opacity: .5; transition: opacity .3s ease, border-color .3s ease; }
        .pgal .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs .pdp-thumb:hover { opacity: .85; }
        .pgal .thumbs .pdp-thumb.on { opacity: 1; border-color: var(--accent); }

        /* ===== the specification column ===== */
        .pinfo .crumb { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); margin-bottom: 22px; }
        .pinfo .crumb a:hover { color: var(--accent-ink); }
        .pinfo h1 { font-family: var(--display); font-weight: 500; font-size: clamp(29px, 3.6vw, 46px); line-height: 1.02; letter-spacing: 0; margin-bottom: 18px; }
        .pinfo .price { font-family: var(--display); font-weight: 500; font-size: 30px; font-variant-numeric: tabular-nums; color: var(--accent-ink); }
        .pinfo .stock { display: inline-flex; align-items: center; gap: 10px; margin-top: 12px; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .pinfo .stock .dot { width: 6px; height: 6px; background: var(--accent); animation: stockpulse 3s ease-in-out infinite; }
        @keyframes stockpulse { 0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 50%, transparent); } 60% { box-shadow: 0 0 0 7px transparent; } }
        @media (prefers-reduced-motion: reduce) { .pinfo .stock .dot { animation: none; } }
        /* pre-line so the merchant's own line breaks survive. A description is
           one of the few fields a shop owner pastes into from elsewhere, and it
           arrives as a list — bullets, sizes, a price line — all of which was
           collapsing into a single paragraph. The text stays escaped; only the
           whitespace is honoured. */
        .qty { margin: 0 0 16px; }
        .qty label { display: block; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); margin-bottom: 8px; }
        .qty-row { display: flex; align-items: center; gap: 10px; }
        .qty-unit { font-size: 13px; font-weight: 500; letter-spacing: .08em; color: var(--muted); }
        .qty input { width: 120px; padding: 13px 14px; background: var(--surface); color: var(--txt); border: 1px solid var(--line2); font: inherit; font-size: 15px; text-align: center; font-variant-numeric: tabular-nums; }
        .qty input:focus { outline: 2px solid var(--accent); outline-offset: 1px; }

        .pinfo p.desc { color: var(--muted); margin: 26px 0 30px; max-width: 50ch; font-size: 15.5px; white-space: pre-line; }

        /* the spec sheet — ruled key/value rows, tabular and unglamorous on
           purpose. A merchant sells the craft; a builder buys the numbers. */
        .specs { border-top: 1px solid var(--line); margin-bottom: 32px; }
        .specs .row { display: grid; grid-template-columns: 40% 1fr; gap: 18px; padding: 14px 0; border-bottom: 1px solid var(--line); align-items: baseline; }
        .specs .row .k { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }
        .specs .row .v { color: var(--txt); font-size: 14.5px; }

        /* ===== option picker — the shared partial's chips, dressed. Covers
           the flat radio list AND the dependent option matrix (length × width),
           which is why the group spacing is styled here too. ===== */
        .pinfo .vp { margin: 0 0 26px; }
        .pinfo .vp-label { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; color: var(--faint); }
        .pinfo .vp-option-body { padding: 12px 18px; border-width: 1px; font-family: var(--body); font-size: 12px; font-weight: 500; letter-spacing: .12em; text-transform: uppercase; background: transparent; clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px); }
        body.no-cut .pinfo .vp-option-body { clip-path: none; }
        .pinfo .vp-option input:checked + .vp-option-body { border-color: var(--accent); }
        .pinfo .vp-mx .vp-group + .vp-group { margin-top: 22px; }

        .pinfo form .btn { margin-bottom: 18px; }
        .pinfo .note { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }

        /* ===== Fullscreen viewer. Esc closes, ←/→ navigate, focus trapped. */
        .pdp-lightbox { position: fixed; inset: 0; z-index: 200; background: rgba(12, 10, 7, .94); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; padding: 6vh 6vw; animation: lbFade .25s ease; }
        .pdp-lightbox[hidden] { display: none; }
        @keyframes lbFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes lbPop { from { opacity: 0; transform: scale(.97); } to { opacity: 1; transform: none; } }
        .pdp-lightbox .lb-stage { position: relative; margin: 0; display: flex; flex-direction: column; align-items: center; gap: 18px; max-width: 92vw; animation: lbPop .3s cubic-bezier(.19, .74, .16, 1); }
        .pdp-lightbox #pdp-lightbox-image { max-width: 86vw; max-height: 80vh; object-fit: contain; }
        .pdp-lightbox .lb-count { font-size: 11px; font-weight: 500; letter-spacing: .28em; color: var(--muted); }
        .pdp-lightbox .lb-close { position: absolute; top: 26px; right: 28px; width: 46px; height: 46px; background: transparent; border: 1px solid var(--line2); color: var(--txt); font-size: 16px; display: grid; place-items: center; transition: border-color .25s ease, color .25s ease; }
        .pdp-lightbox .lb-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 52px; height: 52px; background: transparent; border: 1px solid var(--line2); color: var(--txt); font-family: var(--display); font-size: 26px; line-height: 1; display: grid; place-items: center; transition: border-color .25s ease, color .25s ease; }
        .pdp-lightbox .lb-prev { left: 26px; } .pdp-lightbox .lb-next { right: 26px; }
        .pdp-lightbox .lb-close:hover, .pdp-lightbox .lb-nav:hover { border-color: var(--accent); color: var(--accent-ink); }
        @media (prefers-reduced-motion: reduce) { .pdp-lightbox, .pdp-lightbox .lb-stage { animation: none; } }

        @media (max-width: 900px) {
            .pdp { grid-template-columns: 1fr; gap: 34px; }
            .pgal { position: static; }
        }
        @media (max-width: 560px) {
            .pdp-lightbox { padding: 0; }
            .pdp-lightbox #pdp-lightbox-image { max-width: 94vw; max-height: 74vh; }
            .pdp-lightbox .lb-close { top: 14px; right: 14px; width: 40px; height: 40px; }
            .pdp-lightbox .lb-nav { width: 42px; height: 42px; font-size: 22px; }
            .pdp-lightbox .lb-prev { left: 10px; } .pdp-lightbox .lb-next { right: 10px; }
            .specs .row { grid-template-columns: 1fr; gap: 5px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="pdp">
                @if ($theme->on('gutter_index'))
                    <span class="gx" aria-hidden="true" style="top: 44px;">{{ __('site.storefront.sankevi.gx_product') }}</span>
                @endif

                <div class="pgal rise" style="--d: .1s;">
                    <div class="main cut cut-lg {{ $heroImage ? '' : 'ph' }}" @if ($heroImage) data-pdp-open @endif>
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}">
                            <span class="zoom">{{ __('site.storefront.product.view_fullscreen') }}</span>
                        @else
                            <span class="board-glyph" aria-hidden="true"><i></i></span>
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <div class="thumbs">
                            @foreach ($images as $i => $img)
                                <button type="button" class="pdp-thumb cut cut-sm {{ $i === 0 ? 'on' : '' }}"
                                        data-pdp-src="{{ $img['url'] }}" data-pdp-alt="{{ $img['alt'] }}" data-pdp-index="{{ $i }}"
                                        aria-label="{{ __('site.storefront.product.view_image', ['n' => $i + 1, 'total' => $images->count()]) }}">
                                    <img src="{{ $img['url'] }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pinfo rise" style="--d: .24s;">
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                        @if ($primaryCategory) / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>@endif
                        / <span>{{ $product->name }}</span>
                    </div>

                    <h1>{{ $product->name }}</h1>
                    <div class="price"><span data-vp-price>@money($product->price_cents)</span>@if ($u = $product->priceUnitSuffix())<i class="pu" data-vp-price-unit>{{ $u }}</i>@endif</div>

                    @if (! $product->hasVariants() && $product->stock_quantity > 0)
                        <div class="stock">
                            <span class="dot" aria-hidden="true"></span>
                            @if ($product->stock_quantity < 10)
                                {{ __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) }}
                            @else
                                {{ __('site.storefront.product.in_stock_full') }}
                            @endif
                        </div>
                    @endif

                    @if ($product->description)
                        {{-- pre-line, not nl2br: the merchant's newlines and
                             blank lines survive while the text stays ESCAPED.
                             A description is one of the few fields a shop owner
                             pastes into from elsewhere, and it arrives full of
                             line breaks and bullet characters that were
                             collapsing into one paragraph. --}}
                        <p class="desc">{{ $product->description }}</p>
                    @endif

                    {{-- Specification. Rows come from the platform's own service
                         promises, which is all the structured data a product
                         carries today — but they read as a spec sheet, because
                         that is what this shopper scans for. --}}
                    {{-- EVERY ROW IS A CLAIM, so every row is a switch.
                         These were the platform's own service promises printed
                         unconditionally — free shipping, thirty-day returns,
                         checkout "under a minute". A yard that quotes by
                         enquiry offers none of the three, and the shipping row
                         printed its title with an EMPTY value whenever no
                         free-shipping threshold was set: a promise with nothing
                         behind it. Off by default would break every existing
                         store, so they default on and each can be turned off.
                         With all four off the block goes entirely rather than
                         leaving a ruled empty box. --}}
                    @php
                        $fsAmt = $store->freeShippingAmount();
                        $specRows = [];

                        /*
                         | The merchant's own rows win outright. A yard that
                         | delivers in three days and a florist that delivers in
                         | three hours were advertising the same sentence, and
                         | neither had written it — so anything typed here
                         | REPLACES the platform's promises rather than joining
                         | them, including the category row. Half the block
                         | theirs and half ours would be the harder thing to
                         | reason about, not the kinder one.
                         */
                        foreach ($product->specRows() ?? [] as $gvRow) {
                            $specRows[] = [$gvRow['label'], $gvRow['value']];
                        }
                        if (! $specRows) {
                            if ($theme->on('spec_shipping') && $fsAmt) {
                                $specRows[] = [__('site.storefront.value_props.shipping_title'), __('site.storefront.value_props.shipping_sub', ['amount' => $fsAmt])];
                            }
                            if ($theme->on('spec_returns')) {
                                $specRows[] = [__('site.storefront.value_props.returns_title'), __('site.storefront.value_props.returns_sub')];
                            }
                            if ($theme->on('spec_checkout')) {
                                $specRows[] = [__('site.storefront.value_props.checkout_title'), __('site.storefront.value_props.checkout_sub')];
                            }
                            if ($theme->on('spec_category') && $primaryCategory) {
                                $specRows[] = [__('site.storefront.controls.category'), $primaryCategory->name];
                            }
                        }
                    @endphp
                    @if ($specRows)
                        <div class="specs">
                            @foreach ($specRows as [$k, $v])
                                <div class="row"><span class="k">{{ $k }}</span><span class="v">{{ $v }}</span></div>
                            @endforeach
                        </div>
                    @endif

                    <form method="post" action="/cart/add/{{ $product->slug }}" data-gv-add>
                        @csrf
                        @if ($product->hasVariants())
                            {{-- Flat list or dependent option matrix — the partial
                                 decides; the theme only supplies the skin. --}}
                            @include('storefront.partials.variant-picker')
                        @endif

                        {{-- HOW MANY. A whole number, because everything in
                             this catalogue is a countable thing — boards,
                             brackets, pins — and half a bracket is not an order.
                             There was no quantity field at all before: a
                             customer wanting fifty boards had to add one, fifty
                             times, or fix it in the cart afterwards. --}}
                        <div class="qty">
                            <label for="gv-qty">{{ __('site.storefront.product.quantity') }}</label>
                            <div class="qty-row">
                                <input type="number" id="gv-qty" name="quantity"
                                       value="1" min="1" step="1" inputmode="numeric"
                                       pattern="[0-9]*"
                                       @if ($product->isPricedByMeasure()) aria-describedby="gv-qty-unit" @endif>
                                {{-- The unit the number is counted in, beside the
                                     box rather than only in the price. A product
                                     quoted per m² is ordered in m², and without
                                     this the field is a bare number that could
                                     mean either. Products sold by the piece show
                                     nothing: "1 бр." beside every quantity is
                                     noise. --}}
                                @if ($product->isPricedByMeasure())
                                    <span class="qty-unit" id="gv-qty-unit">{{ $product->priceUnitShort() }}</span>
                                @endif
                            </div>
                        </div>

                        <button type="submit" class="btn block" data-vp-submit @if ($product->hasVariants()) disabled @endif>
                            {{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span>
                        </button>
                    </form>
                    @if (trim(__('site.storefront.sankevi.pdp_note')) !== '')
                        <p class="note">{{ __('site.storefront.sankevi.pdp_note') }}</p>
                    @endif

                    @include('storefront.partials.sticky-atc', ['product' => $product])
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head">
                    <div>
                        <h2>{{ __('site.storefront.featured.h2') }}</h2>
                    </div>
                    <a class="more" href="/">{{ __('site.storefront.featured.browse_all') }}</a>
                </div>
                <div class="shelf {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}" style="--sheet-label: '{{ str_replace(['\\', '\''], '', $theme->label('sheet_marks')) }} '">
                    @foreach ($related as $rp)
                        @include('themes.sankevi._card', ['product' => $rp, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @if ($heroImage)
        {{-- Fullscreen viewer. Hidden until the main image is clicked. Esc
             closes, ←/→ navigate, focus is trapped while open and returned to
             the opener on close. --}}
        <div class="pdp-lightbox" id="pdpLightbox" role="dialog" aria-modal="true"
             aria-label="{{ __('site.storefront.product.gallery_label') }}" hidden>
            <button type="button" class="lb-close" data-pdp-close aria-label="{{ __('site.storefront.product.close') }}">✕</button>
            @if ($images->count() > 1)
                <button type="button" class="lb-nav lb-prev" data-pdp-prev aria-label="{{ __('site.storefront.product.prev_image') }}">‹</button>
                <button type="button" class="lb-nav lb-next" data-pdp-next aria-label="{{ __('site.storefront.product.next_image') }}">›</button>
            @endif
            <figure class="lb-stage">
                <img id="pdp-lightbox-image" src="" alt="{{ $product->name }}">
                @if ($images->count() > 1)<figcaption class="lb-count" id="pdpLbCount"></figcaption>@endif
            </figure>
        </div>
    @endif

    @push('scripts')
        <script>
            // Gallery: thumbnail → hero swap + click-to-zoom fullscreen viewer.
            // One controller so the active thumb, the hero image and the open
            // lightbox always stay on the same frame.
            (function () {
                var main = document.getElementById('pdp-main-image');
                if (! main) return;

                // Image set is rendered server-side so the viewer works even when
                // a product has a single image (no thumbnail strip).
                var images = @json($images->map(fn ($i) => ['src' => $i['url'], 'alt' => $i['alt']])->values());
                var current = 0;
                var lb = document.getElementById('pdpLightbox');
                var lbImg = document.getElementById('pdp-lightbox-image');
                var lbCount = document.getElementById('pdpLbCount');
                var lastFocus = null;

                function setMain(i) {
                    if (! images[i]) return;
                    current = i;
                    main.src = images[i].src;
                    main.alt = images[i].alt || '';
                    document.querySelectorAll('.pgal .thumbs .pdp-thumb').forEach(function (t) {
                        t.classList.toggle('on', parseInt(t.dataset.pdpIndex, 10) === i);
                    });
                    if (lb && ! lb.hidden) syncLightbox();
                }
                document.querySelectorAll('.pgal .thumbs .pdp-thumb[data-pdp-index]').forEach(function (thumb) {
                    thumb.addEventListener('click', function () { setMain(parseInt(thumb.dataset.pdpIndex, 10)); });
                });

                if (! lb || ! lbImg) return;

                function syncLightbox() {
                    lbImg.src = images[current].src;
                    lbImg.alt = images[current].alt || '';
                    if (lbCount) lbCount.textContent = (current + 1) + ' / ' + images.length;
                }
                function open() {
                    lastFocus = document.activeElement;
                    syncLightbox();
                    lb.hidden = false;
                    document.body.style.overflow = 'hidden';
                    var c = lb.querySelector('[data-pdp-close]');
                    if (c) c.focus();
                }
                function close() {
                    lb.hidden = true;
                    document.body.style.overflow = '';
                    if (lastFocus && lastFocus.focus) lastFocus.focus();
                }
                function step(d) { setMain((current + d + images.length) % images.length); }

                document.querySelectorAll('[data-pdp-open]').forEach(function (el) {
                    el.addEventListener('click', open);
                });
                lb.querySelectorAll('[data-pdp-close]').forEach(function (el) { el.addEventListener('click', close); });
                var prev = lb.querySelector('[data-pdp-prev]'); if (prev) prev.addEventListener('click', function () { step(-1); });
                var next = lb.querySelector('[data-pdp-next]'); if (next) next.addEventListener('click', function () { step(1); });
                lb.addEventListener('click', function (e) { if (e.target === lb || e.target.classList.contains('lb-stage')) close(); });

                document.addEventListener('keydown', function (e) {
                    if (lb.hidden) return;
                    if (e.key === 'Escape') { close(); }
                    else if (e.key === 'ArrowLeft') { step(-1); }
                    else if (e.key === 'ArrowRight') { step(1); }
                    else if (e.key === 'Tab') {
                        var f = Array.prototype.slice.call(lb.querySelectorAll('button'));
                        if (! f.length) return;
                        var first = f[0], last = f[f.length - 1];
                        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                        else if (! e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                    }
                });
            })();
        </script>
    @endpush

@endsection
