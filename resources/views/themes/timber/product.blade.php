@php
    $title = $product->name;
@endphp
@extends('themes.timber.layout')

@section('content')
    @php
        $images = $product->allImages()->take(4);
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
        /* Timber PDP — sticky framed board gallery left, spec-sheet info column
           right. Light surfaces, mono spec labels, condensed-caps price. */
        .pdp { display: grid; grid-template-columns: .85fr 1.15fr; gap: 50px; padding: 20px 0 0; align-items: start; }

        /* gallery — sticky hero + thumbnail row */
        .pgal { position: sticky; top: calc(var(--header-height) + 24px); }
        .pgal .main { height: 540px; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; position: relative; display: grid; place-items: center; background: var(--surface); box-shadow: 0 2px 0 0 var(--line); }
        /* a mono spec tag in the corner — the cutting-ledger voice */
        /* the label comes from the merchant; take it from the inline custom
           property (set on .pgal below) rather than interpolating into this
           <style> block — CSS never decodes the HTML entities Blade emits. */
        .pgal .main::after { content: var(--lot-label, "LOT ") "01"; position: absolute; bottom: 12px; right: 14px; z-index: 2; font-family: var(--mono); font-size: 10px; letter-spacing: .14em; color: var(--muted); background: color-mix(in srgb, var(--surface) 82%, transparent); border: 1px solid var(--line); border-radius: 4px; padding: 4px 10px; pointer-events: none; }
        .pgal .main[data-pdp-open] { cursor: zoom-in; }
        .pgal .main .plank-mark { width: 190px; height: 190px; }
        .pgal .main .plank-mark i { height: 30px; }
        .pgal .main .plank-mark i:nth-child(2) { top: 38px; }
        .pgal .main .plank-mark i:nth-child(3) { top: 76px; }
        .pgal .main .plank-mark i:nth-child(4) { top: 114px; }
        .pgal .main .plank-mark i:nth-child(5) { top: 152px; }
        .pgal .main img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs { display: flex; gap: 12px; margin-top: 12px; }
        .pgal .thumbs .pdp-thumb { flex: 1; height: 80px; border-radius: 6px; cursor: pointer; opacity: .6; overflow: hidden; border: 1px solid var(--line); padding: 0; background: var(--surface2); transition: opacity .2s ease, outline-color .2s ease; outline: 2px solid transparent; outline-offset: 2px; }
        .pgal .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs .pdp-thumb.on, .pgal .thumbs .pdp-thumb:hover { opacity: 1; }
        .pgal .thumbs .pdp-thumb.on { outline-color: var(--accent); }

        /* info column — the spec sheet */
        .pinfo .crumb { font-family: var(--mono); font-size: 12px; letter-spacing: .02em; color: var(--faint); margin-bottom: 14px; }
        .pinfo .crumb a:hover { color: var(--accent-deep); }
        .pinfo .cat { font-family: var(--mono); font-size: 12px; letter-spacing: .02em; text-transform: uppercase; color: var(--accent-deep); }
        .pinfo h1 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(40px, 4.6vw, 60px); line-height: .96; margin: 8px 0 12px; }
        .pinfo .price { font-family: var(--display); font-weight: 700; font-size: 32px; font-variant-numeric: tabular-nums; margin: 16px 0; color: var(--txt); }
        .pinfo .stock { font-size: 13px; color: var(--muted); margin: -6px 0 18px; display: inline-flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 8px; height: 8px; border-radius: 2px; background: var(--accent); animation: stockpulse 2.6s ease-in-out infinite; }
        @keyframes stockpulse { 0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 45%, transparent); } 70% { box-shadow: 0 0 0 8px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }
        @media (prefers-reduced-motion: reduce) { .pinfo .stock .dot { animation: none; } }
        .pinfo p.desc { color: var(--muted); margin-bottom: 22px; max-width: 48ch; }
        /* the spec rows — ruled cutting-list lines */
        .pinfo ul.perks { list-style: none; margin-bottom: 24px; border-top: 2px solid var(--txt); }
        .pinfo ul.perks li { padding: 11px 0; border-bottom: 1px solid var(--line); font-size: 14px; display: flex; gap: 10px; }
        .pinfo ul.perks li::before { content: "▮"; color: var(--accent); }

        .opt { margin-bottom: 20px; }
        .opt .ol { font-family: var(--mono); font-size: 11px; letter-spacing: .02em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }

        /* ===== Fullscreen image viewer (lightbox). Esc closes, ←/→ navigate,
           focus is trapped while open. ===== */
        .pdp-lightbox { position: fixed; inset: 0; z-index: 200; background: rgba(36, 28, 18, .88); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 6vh 6vw; animation: lbFade .22s ease; }
        .pdp-lightbox[hidden] { display: none; }
        @keyframes lbFade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes lbPop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
        .pdp-lightbox .lb-stage { position: relative; margin: 0; display: flex; flex-direction: column; align-items: center; gap: 16px; max-width: 92vw; animation: lbPop .26s cubic-bezier(.19, .7, .16, 1); }
        .pdp-lightbox #pdp-lightbox-image { max-width: 86vw; max-height: 80vh; object-fit: contain; border-radius: 6px; background: var(--surface); padding: 10px; box-shadow: 0 30px 70px -26px rgba(0, 0, 0, .6); }
        .pdp-lightbox .lb-count { font-family: var(--mono); font-size: 14px; color: #f0e7d6; letter-spacing: .08em; }
        .pdp-lightbox .lb-close { position: absolute; top: 24px; right: 26px; width: 50px; height: 50px; border-radius: 8px; background: var(--surface); color: var(--txt); border: none; box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .55); font-size: 17px; line-height: 1; display: grid; place-items: center; transition: background-color .22s ease, color .22s ease; }
        .pdp-lightbox .lb-close:hover { background: var(--accent); color: var(--on-accent); }
        .pdp-lightbox .lb-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 56px; height: 56px; border-radius: 8px; background: var(--surface); color: var(--txt); border: none; box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .55); font-family: var(--display); font-size: 30px; line-height: 1; display: grid; place-items: center; transition: background-color .22s ease, color .22s ease; }
        .pdp-lightbox .lb-prev { left: 24px; }
        .pdp-lightbox .lb-next { right: 24px; }
        .pdp-lightbox .lb-nav:hover { background: var(--accent); color: var(--on-accent); }
        @media (prefers-reduced-motion: reduce) {
            .pdp-lightbox, .pdp-lightbox .lb-stage { animation: none; }
        }
        @media (max-width: 560px) {
            .pdp-lightbox { padding: 0; }
            .pdp-lightbox #pdp-lightbox-image { max-width: 94vw; max-height: 74vh; }
            .pdp-lightbox .lb-close { top: 14px; right: 14px; }
            .pdp-lightbox .lb-nav { width: 46px; height: 46px; font-size: 24px; }
            .pdp-lightbox .lb-prev { left: 10px; } .pdp-lightbox .lb-next { right: 10px; }
        }

        @media (max-width: 880px) {
            .pdp { grid-template-columns: 1fr; gap: 34px; }
            .pgal { position: static; }
            .pgal .main { height: 420px; }
        }
    </style>

    <main>
        <div class="wrap" style="padding-top: 24px;">
            <div class="pdp">
                <div class="pgal" data-gv-reveal="fade" style="--lot-label: '{{ str_replace(['\\', "'"], '', $theme->label('lot_stamps')) }} '">
                    <div class="main" @if ($heroImage) data-pdp-open @endif>
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}">
                        @else
                            <span class="plank-mark" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <div class="thumbs">
                            @foreach ($images as $i => $img)
                                <button type="button" class="pdp-thumb {{ $i === 0 ? 'on' : '' }}"
                                        data-pdp-src="{{ $img['url'] }}" data-pdp-alt="{{ $img['alt'] }}" data-pdp-index="{{ $i }}"
                                        aria-label="{{ __('site.storefront.product.view_image', ['n' => $i + 1, 'total' => $images->count()]) }}">
                                    <img src="{{ $img['url'] }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pinfo" data-gv-reveal data-gv-delay="0.12">
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                        @if ($primaryCategory) / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>@endif
                        / <span>{{ $product->name }}</span>
                    </div>

                    @if ($primaryCategory)<div class="cat">{{ $primaryCategory->name }}</div>@endif
                    <h1 data-gv-split>{{ $product->name }}</h1>
                    <div class="price"><span data-vp-price>@money($product->price_cents)</span></div>

                    @if (! $product->hasVariants() && $product->stock_quantity > 0)
                        <div class="stock">
                            <span class="dot"></span>
                            @if ($product->stock_quantity < 10)
                                {{ __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) }}
                            @else
                                {{ __('site.storefront.product.in_stock_full') }}
                            @endif
                        </div>
                    @endif

                    @if ($product->description)
                        <p class="desc">{{ $product->description }}</p>
                    @endif

                    <ul class="perks">
                        <li>{{ __('site.storefront.value_props.shipping_sub') }}</li>
                        <li>{{ __('site.storefront.value_props.returns_sub') }}</li>
                        <li>{{ __('site.storefront.value_props.checkout_sub') }}</li>
                    </ul>

                    <form method="post" action="/cart/add/{{ $product->slug }}" data-gv-add>
                        @csrf
                        @if ($product->hasVariants())
                            <div class="opt">
                                @include('storefront.partials.variant-picker')
                            </div>
                        @endif

                        <button type="submit" class="btn block" data-vp-submit @if ($product->hasVariants()) disabled @endif>
                            {{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span>
                        </button>
                    </form>

                    @include('storefront.partials.sticky-atc', ['product' => $product])
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head" style="margin-top: 70px;">
                    <span class="kicker">{{ __('site.storefront.timber.related_eyebrow') }}</span>
                    <h2>{{ __('site.storefront.featured.h2') }}</h2>
                    <a class="more" href="/">{{ __('site.storefront.featured.browse_all') }} →</a>
                </div>
                <div class="racks {{ $theme->on('lot_stamps') ? '' : 'no-lot' }}" style="--lot-label: '{{ str_replace(['\\', '\''], '', $theme->label('lot_stamps')) }} '">
                    @foreach ($related as $rp)
                        @include('themes.timber._card', ['product' => $rp, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @if ($heroImage)
        {{-- Fullscreen image viewer. Hidden until the main image is clicked.
             Keyboard: Esc closes, ←/→ navigate, focus is trapped while open
             and returned to the opener on close. --}}
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
