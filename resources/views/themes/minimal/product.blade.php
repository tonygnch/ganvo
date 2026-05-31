@php $title = $product->name; @endphp
@extends('themes.minimal.layout')

@section('content')
    @php
        $primaryCategoryId = \Illuminate\Support\Facades\DB::table('category_product')->where('product_id', $product->id)->value('category_id');
        $primaryCategory = $primaryCategoryId ? \App\Models\Category::find($primaryCategoryId) : null;
        $relatedQ = \App\Models\Product::query()->where('tenant_id', $product->tenant_id)->where('is_active', true)->where('id', '!=', $product->id);
        if ($primaryCategoryId) { $relatedQ->whereHas('categories', fn ($q) => $q->where('categories.id', $primaryCategoryId)); }
        $related = $relatedQ->limit(4)->get();
        if ($related->isEmpty()) { $related = \App\Models\Product::query()->where('tenant_id', $product->tenant_id)->where('is_active', true)->where('id', '!=', $product->id)->limit(4)->get(); }
    @endphp

    <style>
        .pdp { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; padding: 30px 0 0; align-items: start; }
        .pdp .product-gallery .pg-main { height: 540px; background: linear-gradient(140deg,#f6dccd,#f3d2c3); border-radius: 30px; }
        .pdp .product-gallery .pg-thumbs { display: flex; gap: 14px; margin-top: 16px; grid-template-columns: none; }
        .pdp .product-gallery .pg-thumb { flex: 1; height: 96px; border-radius: 16px; opacity: .65; border: 0; background: var(--blush); }
        .pdp .product-gallery .pg-thumb.is-active { opacity: 1; outline: 2px solid var(--accent); outline-offset: 2px; }
        .pinfo .cat { font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: var(--accent); font-weight: 700; }
        .pinfo h1 { font-family: var(--display); font-size: clamp(36px,4.4vw,54px); line-height: 1.04; margin: 10px 0 12px; }
        .pinfo .price { font-size: 26px; color: var(--accent); font-weight: 700; margin: 18px 0; }
        .pinfo .price small { font-size: 13px; color: var(--muted); font-weight: 400; }
        .pinfo p.desc { color: #7a5e54; margin-bottom: 24px; }
        .stockline { font-size: 13px; color: var(--muted); margin-bottom: 18px; }
        .pinfo .vp { margin-bottom: 24px; }
        .pinfo .vp-label { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
        .pinfo .vp-options { display: flex; gap: 10px; flex-wrap: wrap; }
        .pinfo .vp-option { position: relative; cursor: pointer; }
        .pinfo .vp-option input { position: absolute; opacity: 0; pointer-events: none; }
        .pinfo .vp-option-body { display: inline-flex; align-items: center; border: 1.5px solid var(--line); background: var(--card); border-radius: 14px; padding: 13px 20px; font-size: 14px; font-weight: 600; }
        .pinfo .vp-option input:checked + .vp-option-body { border-color: var(--accent); color: var(--accent); }
        .pinfo .vp-option.vp-out .vp-option-body { opacity: .4; text-decoration: line-through; }
        .pinfo .vp-option-price, .pinfo .vp-option-meta { display: none; }
        .add-row { display: flex; gap: 10px; }
        .add-row .btn { flex: 1; }
        .wishlist { width: 54px; border: 1.5px solid var(--line); background: var(--card); color: var(--ink); border-radius: 99px; font-size: 18px; }
        .wishlist:hover { border-color: var(--accent); color: var(--accent); }
        @media (max-width: 1000px) { .pdp { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap" style="padding-top:30px">
            <div class="pdp">
                <div class="pgal rv">@include('storefront.partials.product-gallery')</div>
                <div class="pinfo rv">
                    @if ($primaryCategory)<div class="cat">{{ $primaryCategory->name }}</div>@endif
                    <h1>{{ $product->name }}</h1>
                    <div class="price"><span data-vp-price>@money($product->price_cents)</span> <small>{{ __('site.storefront.product.tax_included') }}</small></div>
                    @if (! $product->hasVariants() && $product->stock_quantity > 0)
                        <div class="stockline">{{ $product->stock_quantity < 10 ? __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) : __('site.storefront.product.in_stock_full') }}</div>
                    @endif
                    @if ($product->description)<p class="desc">{{ $product->description }}</p>@endif
                    <form method="post" action="/cart/add/{{ $product->slug }}">
                        @csrf
                        @if ($product->hasVariants())@include('storefront.partials.variant-picker')@endif
                        <div class="add-row">
                            <button type="submit" class="btn block" data-vp-submit>{{ __('site.storefront.product.add_to_cart') }} — <span data-vp-submit-price>@money($product->price_cents)</span></button>
                            <button type="button" class="wishlist" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                        </div>
                    </form>
                </div>
            </div>
            @if ($related->isNotEmpty())
                <div class="sec-head rv"><div class="k">{{ __('site.storefront.featured.eyebrow') }}</div><h2>{{ __('site.storefront.featured.h2') }}</h2></div>
                <div class="pgrid">@foreach ($related as $product)@include('themes.minimal._card')@endforeach</div>
            @endif
        </div>
    </main>
@endsection
