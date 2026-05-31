@php
    $title = $product->name;
@endphp
@extends('themes.tech.layout')

@section('content')
    @php
        $primaryCategoryId = \Illuminate\Support\Facades\DB::table('category_product')->where('product_id', $product->id)->value('category_id');
        $primaryCategory = $primaryCategoryId ? \App\Models\Category::find($primaryCategoryId) : null;
        $relatedQ = \App\Models\Product::query()->where('tenant_id', $product->tenant_id)->where('is_active', true)->where('id', '!=', $product->id);
        if ($primaryCategoryId) { $relatedQ->whereHas('categories', fn ($q) => $q->where('categories.id', $primaryCategoryId)); }
        $related = $relatedQ->limit(4)->get();
        if ($related->isEmpty()) {
            $related = \App\Models\Product::query()->where('tenant_id', $product->tenant_id)->where('is_active', true)->where('id', '!=', $product->id)->limit(4)->get();
        }
    @endphp

    <style>
        .pdp { display: grid; grid-template-columns: 1.1fr 1fr; gap: 50px; padding: 34px 0 0; align-items: start; }
        /* the shared product-gallery partial renders .pg-main + .pg-thumbs;
           restyle for the Volt dark look. */
        .pdp .product-gallery .pg-main { height: 520px; background: linear-gradient(160deg,#222a3a,#0f121a); border: 1px solid var(--line); border-radius: 14px; }
        .pdp .product-gallery .pg-thumbs { display: flex; gap: 12px; margin-top: 14px; grid-template-columns: none; }
        .pdp .product-gallery .pg-thumb { flex: 1; height: 84px; opacity: .6; background: var(--surface2); border: 1px solid var(--line); border-radius: 8px; }
        .pdp .product-gallery .pg-thumb.is-active { opacity: 1; border-color: var(--accent); }
        .pdp .product-gallery .pg-placeholder { color: var(--faint); }

        .pinfo .cat { font-family: var(--mono); font-size: 12px; color: var(--accent); text-transform: uppercase; }
        .pinfo h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(34px,4.2vw,52px); letter-spacing: -.02em; line-height: 1.02; margin: 8px 0 12px; }
        .pinfo .price { font-family: var(--mono); font-size: 30px; color: var(--accent); margin: 18px 0; }
        .pinfo .price small { font-size: 12px; color: var(--muted); }
        .pinfo p.desc { color: var(--muted); margin-bottom: 24px; }
        .stockline { font-family: var(--mono); font-size: 12px; color: var(--muted); margin-bottom: 18px; }
        .stockline b { color: var(--accent); }

        /* variant picker → Volt "finish" buttons */
        .pinfo .vp { margin-bottom: 22px; }
        .pinfo .vp-label { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 10px; }
        .pinfo .vp-options { display: flex; gap: 10px; flex-wrap: wrap; }
        .pinfo .vp-option { position: relative; cursor: pointer; }
        .pinfo .vp-option input { position: absolute; opacity: 0; pointer-events: none; }
        .pinfo .vp-option-body { display: inline-flex; align-items: center; padding: 11px 16px; border: 1px solid var(--line); background: var(--surface); color: var(--txt); border-radius: 8px; font-size: 13px; }
        .pinfo .vp-option input:checked + .vp-option-body { border-color: var(--accent); color: var(--accent); }
        .pinfo .vp-option.vp-out .vp-option-body { opacity: .4; text-decoration: line-through; }
        .pinfo .vp-option-price, .pinfo .vp-option-meta { display: none; }

        .add-row { display: flex; gap: 10px; }
        .add-row .btn { flex: 1; }
        .wishlist { width: 52px; border: 1px solid var(--line); background: var(--surface); color: var(--txt); border-radius: 6px; }
        .wishlist:hover { border-color: var(--accent); color: var(--accent); }

        @media (max-width: 1000px) { .pdp { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap" style="padding-top:30px">
            <div class="pdp">
                <div class="pgal rv">
                    @include('storefront.partials.product-gallery')
                </div>
                <div class="pinfo rv">
                    @if ($primaryCategory)<div class="cat">// {{ $primaryCategory->name }}</div>@endif
                    <h1>{{ $product->name }}</h1>
                    <div class="price"><span data-vp-price>@money($product->price_cents)</span> <small>{{ __('site.storefront.product.tax_included') }}</small></div>

                    @if (! $product->hasVariants() && $product->stock_quantity > 0)
                        <div class="stockline">// {{ $product->stock_quantity < 10 ? __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) : __('site.storefront.product.in_stock_full') }}</div>
                    @endif

                    @if ($product->description)<p class="desc">{{ $product->description }}</p>@endif

                    <form method="post" action="/cart/add/{{ $product->slug }}">
                        @csrf
                        @if ($product->hasVariants())
                            @include('storefront.partials.variant-picker')
                        @endif
                        <div class="add-row">
                            <button type="submit" class="btn block" data-vp-submit>{{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span></button>
                            <button type="button" class="wishlist" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="sec-head rv"><h2>{{ __('site.storefront.featured.h2') }}</h2><a href="/">{{ __('site.storefront.featured.browse_all') }} →</a></div>
                <div class="pgrid">
                    @foreach ($related as $product)
                        @include('themes.tech._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
            @endif
        </div>
    </main>
@endsection
