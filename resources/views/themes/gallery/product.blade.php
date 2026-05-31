@php $title = $product->name; @endphp
@extends('themes.gallery.layout')

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
        .pdp { display: grid; grid-template-columns: 1.1fr 1fr; gap: 54px; padding: 34px 0 0; align-items: start; }
        .pdp .product-gallery .pg-main { height: 560px; background: var(--soft); border-radius: 18px; }
        .pdp .product-gallery .pg-thumbs { display: flex; gap: 12px; margin-top: 14px; grid-template-columns: none; }
        .pdp .product-gallery .pg-thumb { flex: 1; height: 90px; opacity: .6; border: 0; border-radius: 10px; background: var(--soft); }
        .pdp .product-gallery .pg-thumb.is-active { opacity: 1; outline: 2px solid var(--accent); outline-offset: 2px; }
        .pinfo .cat { font-size: 13px; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .pinfo h1 { font-family: var(--display); font-weight: 700; font-size: clamp(34px,4.2vw,52px); letter-spacing: -.02em; line-height: 1.03; margin: 8px 0 12px; }
        .pinfo .price { font-family: var(--display); font-weight: 600; font-size: 28px; margin: 16px 0; }
        .pinfo .price small { font-size: 13px; color: var(--muted); font-weight: 400; }
        .pinfo p.desc { color: var(--muted); margin-bottom: 24px; }
        .stockline { font-size: 13px; color: var(--muted); margin-bottom: 18px; }
        .pinfo .vp { margin-bottom: 22px; }
        .pinfo .vp-label { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
        .pinfo .vp-options { display: flex; gap: 10px; flex-wrap: wrap; }
        .pinfo .vp-option { position: relative; cursor: pointer; }
        .pinfo .vp-option input { position: absolute; opacity: 0; pointer-events: none; }
        .pinfo .vp-option-body { display: inline-flex; align-items: center; padding: 11px 18px; border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 8px; font-size: 13px; }
        .pinfo .vp-option input:checked + .vp-option-body { border-color: var(--accent); color: var(--accent); }
        .pinfo .vp-option.vp-out .vp-option-body { opacity: .4; text-decoration: line-through; }
        .pinfo .vp-option-price, .pinfo .vp-option-meta { display: none; }
        .add-row { display: flex; gap: 10px; }
        .add-row .btn { flex: 1; }
        .wishlist { width: 54px; border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 8px; font-size: 18px; }
        .wishlist:hover { border-color: var(--accent); color: var(--accent); }
        .accordion { border-top: 1px solid var(--line); margin-top: 30px; }
        .accordion details { border-bottom: 1px solid var(--line); }
        .accordion summary { padding: 16px 0; font-size: 14px; font-weight: 600; display: flex; justify-content: space-between; list-style: none; cursor: pointer; }
        .accordion summary::-webkit-details-marker { display: none; }
        .accordion summary .mk::after { content: "+"; } .accordion details[open] summary .mk::after { content: "−"; }
        .accordion .ac-body { padding: 0 0 18px; color: var(--muted); font-size: 14px; }
        @media (max-width: 1000px) { .pdp { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap" style="padding-top:34px">
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
                    <div class="accordion">
                        <details open><summary>{{ __('site.storefront.product.perks.shipping') }}<span class="mk"></span></summary><div class="ac-body">{{ __('site.storefront.value_props.shipping_sub') }}</div></details>
                        <details><summary>{{ __('site.storefront.product.perks.returns') }}<span class="mk"></span></summary><div class="ac-body">{{ __('site.storefront.value_props.returns_sub') }}</div></details>
                        <details><summary>{{ __('site.storefront.product.perks.fast') }}<span class="mk"></span></summary><div class="ac-body">{{ __('site.storefront.value_props.checkout_sub') }}</div></details>
                    </div>
                </div>
            </div>
            @if ($related->isNotEmpty())
                <div class="sec-head rv"><h2>{{ __('site.storefront.featured.h2') }}</h2><a href="/">{{ __('site.storefront.featured.browse_all') }} →</a></div>
                <div class="pgrid">@foreach ($related as $product)@include('themes.gallery._card', ['badge' => null])@endforeach</div>
            @endif
        </div>
    </main>
@endsection
