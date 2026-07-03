@php
    $title = $collection->title;
@endphp
@extends('themes.forma.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        /* ===== Collection banner ===== */
        .coll-hero { position: relative; border-radius: 24px; overflow: hidden; margin-top: 32px; min-height: 320px; display: flex; align-items: flex-end; background: var(--ink); }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .7; }
        .coll-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(20, 22, 28, .82), rgba(20, 22, 28, .12) 60%, transparent); z-index: 1; }
        .coll-hero .overlay { position: relative; z-index: 2; padding: 44px 40px; color: #fff; max-width: 640px; }
        .coll-hero .overlay .kicker { color: color-mix(in srgb, var(--accent) 50%, #fff); display: block; margin-bottom: 12px; }
        .coll-hero .overlay h1 { font-family: var(--display); font-weight: 800; font-size: clamp(40px, 5vw, 64px); letter-spacing: -.02em; line-height: 1; }
        .coll-hero .overlay h1 em { font-style: normal; color: color-mix(in srgb, var(--accent) 50%, #fff); }
        .coll-hero .overlay p { font-size: 16px; max-width: 52ch; margin-top: 12px; color: rgba(255, 255, 255, .85); }

        /* count toolbar */
        .toolbar { display: flex; align-items: center; gap: 14px; margin: 32px 0 8px; }
        .toolbar .count { font-family: var(--mono); font-size: 12px; color: var(--accent); }
        .toolbar .rule { flex: 1; height: 1px; background: var(--line); }
        .toolbar .crumb { font-family: var(--mono); font-size: 12px; color: var(--muted); }
        .toolbar .crumb a:hover { color: var(--accent); }

        .coll-empty { text-align: center; padding: 70px 24px; font-family: var(--mono); font-size: 14px; color: var(--muted); }
    </style>

    <main>
        <div class="wrap">
            @if ($bannerUrl)
                <section class="coll-hero reveal">
                    <div class="tape r" aria-hidden="true"></div>
                    <img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">
                    <div class="overlay">
                        <span class="kicker">{{ __('site.storefront.featured.eyebrow') }}</span>
                        <h1>{{ $collection->title }}</h1>
                        @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                    </div>
                </section>
            @else
                <div class="page-head reveal">
                    <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a></div>
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                </div>
            @endif

            <div class="toolbar reveal">
                <span class="count">// {{ $products->total() }}</span>
                <span class="rule"></span>
                <span class="crumb">{{ __('site.storefront.shop_all.h2') }}</span>
            </div>

            @if ($products->isEmpty())
                <div class="coll-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="blooms">
                    @foreach ($products as $product)
                        @include('themes.forma._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
