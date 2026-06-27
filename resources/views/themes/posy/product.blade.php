@php
    $title = $product->name;
@endphp
@extends('themes.posy.layout')

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
        /* Posy PDP — sticky editorial gallery on the left, info column on the
           right. Soft rounded cards, washi tape on the hero, serif accent price. */
        .pdp { display: grid; grid-template-columns: 1fr 1fr; gap: 54px; padding: 20px 0 0; align-items: start; }

        /* gallery — sticky hero + thumbnail row */
        .pgal { position: sticky; top: calc(var(--header-height) + 24px); }
        .pgal .main { height: 540px; border-radius: 8px; overflow: hidden; position: relative; box-shadow: 0 24px 50px -28px rgba(40, 50, 31, .5); cursor: zoom-in; }
        .pgal .main .tape { width: 120px; height: 30px; }
        .pgal .main img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs { display: flex; gap: 14px; margin-top: 16px; }
        .pgal .thumbs .pdp-thumb { flex: 1; height: 96px; border-radius: 5px; cursor: pointer; opacity: .6; overflow: hidden; border: none; padding: 0; background: var(--soft); transition: opacity .2s ease, outline-color .2s ease; outline: 2px solid transparent; outline-offset: 2px; }
        .pgal .thumbs .pdp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pgal .thumbs .pdp-thumb.on, .pgal .thumbs .pdp-thumb:hover { opacity: 1; }
        .pgal .thumbs .pdp-thumb.on { outline-color: var(--accent); }

        /* info column */
        .pinfo .crumb { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 14px; }
        .pinfo .crumb a:hover { color: var(--accent); }
        .pinfo .cat { font-size: 13px; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .pinfo h1 { font-family: var(--display); font-size: clamp(36px, 4.4vw, 56px); line-height: 1.02; margin: 8px 0 10px; font-weight: 400; }
        .pinfo h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .pinfo .price { font-family: var(--serif); font-size: 28px; margin: 16px 0; color: var(--accent); }
        .pinfo .stock { font-size: 13px; color: var(--muted); margin: -6px 0 18px; display: inline-flex; align-items: center; gap: 8px; }
        .pinfo .stock .dot { width: 8px; height: 8px; border-radius: 99px; background: var(--accent); }
        .pinfo p.desc { color: var(--muted); margin-bottom: 22px; max-width: 46ch; }
        .pinfo ul.perks { list-style: none; margin-bottom: 24px; }
        .pinfo ul.perks li { padding: 11px 0; border-bottom: 1px solid var(--line); font-size: 14px; display: flex; gap: 10px; }
        .pinfo ul.perks li::before { content: "❧"; color: var(--accent); }

        .opt { margin-bottom: 20px; }
        .opt .ol { font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }

        @media (max-width: 880px) {
            .pdp { grid-template-columns: 1fr; gap: 34px; }
            .pgal { position: static; }
            .pgal .main { height: 440px; }
        }
    </style>

    <main>
        <div class="wrap" style="padding-top: 24px;">
            <div class="pdp">
                <div class="pgal">
                    <div class="main {{ $heroImage ? '' : 'bloomph ph' }}" data-pdp-open>
                        <div class="tape"></div>
                        @if ($heroImage)
                            <img id="pdp-main-image" src="{{ $heroImage }}" alt="{{ $product->name }}">
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

                <div class="pinfo">
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                        @if ($primaryCategory) / <a href="/categories/{{ $primaryCategory->slug }}">{{ $primaryCategory->name }}</a>@endif
                        / <span>{{ $product->name }}</span>
                    </div>

                    @if ($primaryCategory)<div class="cat">{{ $primaryCategory->name }}</div>@endif
                    <h1>{{ $product->name }}</h1>
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

                    <form method="post" action="/cart/add/{{ $product->slug }}">
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
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head" style="margin-top: 70px;">
                    <span class="kicker">{{ __('site.storefront.product.wear_it_with') }}</span>
                    <h2>{{ __('site.storefront.featured.browse_all') }}</h2>
                    <a class="more" href="/">{{ __('site.storefront.featured.browse_all') }} →</a>
                </div>
                <div class="blooms">
                    @foreach ($related as $rp)
                        @include('themes.posy._card', ['product' => $rp, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @push('scripts')
        <script>
            // Posy gallery thumbnail → hero swap. Click a thumb to set the main
            // image; the active thumb gets the accent outline.
            (function () {
                var main = document.getElementById('pdp-main-image');
                if (! main) return;
                document.querySelectorAll('.pgal .thumbs .pdp-thumb[data-pdp-index]').forEach(function (thumb) {
                    thumb.addEventListener('click', function () {
                        main.src = thumb.dataset.pdpSrc;
                        main.alt = thumb.dataset.pdpAlt || '';
                        document.querySelectorAll('.pgal .thumbs .pdp-thumb').forEach(function (t) {
                            t.classList.toggle('on', t === thumb);
                        });
                    });
                });
            })();
        </script>
    @endpush
@endsection
