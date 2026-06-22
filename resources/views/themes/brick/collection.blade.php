@php
    $title = $collection->title;
@endphp
@extends('themes.brick.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        .coll-hero { position: relative; border: 2.5px solid var(--ink); box-shadow: var(--pop-lg); margin-top: 32px; overflow: hidden; background: var(--ink); min-height: 320px; display: flex; align-items: flex-end; }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .65; }
        .coll-hero .overlay { position: relative; z-index: 2; padding: 40px 36px; color: var(--paper); }
        .coll-hero .overlay .sub { display: inline-flex; background: var(--accent); color: var(--ink); font-family: var(--display); font-weight: 800; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; padding: 5px 11px; margin-bottom: 14px; }
        .coll-hero .overlay h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(36px, 6vw, 84px); line-height: .9; letter-spacing: -.03em; }
        .coll-hero .overlay p { font-size: 14px; max-width: 52ch; margin-top: 12px; color: rgba(253,251,240,.85); }

        .toolbar { border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); background: var(--accent); padding: 14px 18px; margin: 30px 0 26px; font-family: var(--display); font-size: 13px; font-weight: 800; text-transform: uppercase; }
        .coll-empty { border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 60px 24px; text-align: center; font-family: var(--display); font-weight: 800; text-transform: uppercase; }
    </style>

    <main>
        <div class="wrap">
            @if ($bannerUrl)
                <section class="coll-hero rv">
                    <img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">
                    <div class="overlay">
                        <div class="sub">{{ __('site.storefront.featured.eyebrow') }}</div>
                        <h1>{{ $collection->title }}</h1>
                        @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                    </div>
                </section>
            @else
                <div class="ed-head rv">
                    <div>
                        <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a></div>
                        <h1>{{ $collection->title }}</h1>
                    </div>
                    @if ($collection->description)<div class="meta">{{ $collection->description }}</div>@endif
                </div>
            @endif

            <div class="toolbar">{{ $products->total() }} · {{ __('site.storefront.shop_all.h2') }}</div>

            @if ($products->isEmpty())
                <div class="coll-empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        @include('themes.brick._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
