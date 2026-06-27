@php
    $title = $collection->title;
@endphp
@extends('themes.posy.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        /* ===== Collection banner ===== */
        .coll-hero { position: relative; border-radius: 14px; overflow: hidden; margin-top: 32px; min-height: 320px; display: flex; align-items: flex-end; background: var(--deep); box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4); }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .7; }
        .coll-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(34, 43, 26, .78), rgba(34, 43, 26, .12) 60%, transparent); z-index: 1; }
        .coll-hero .overlay { position: relative; z-index: 2; padding: 44px 40px; color: #fbfcf5; max-width: 640px; }
        .coll-hero .overlay .kicker { color: #cfe0bb; display: block; margin-bottom: 12px; }
        .coll-hero .overlay h1 { font-family: var(--display); font-weight: 400; font-size: clamp(40px, 5vw, 64px); line-height: 1; }
        .coll-hero .overlay h1 em { font-family: var(--serif); font-style: italic; color: #e8efd9; }
        .coll-hero .overlay p { font-family: var(--serif); font-style: italic; font-size: 18px; max-width: 52ch; margin-top: 12px; color: rgba(251, 252, 245, .9); }
        .coll-hero .tape { top: -12px; }

        /* count toolbar */
        .toolbar { display: flex; align-items: center; gap: 14px; margin: 32px 0 8px; }
        .toolbar .count { font-family: var(--serif); font-style: italic; font-size: 18px; color: var(--accent); }
        .toolbar .rule { flex: 1; height: 1px; background: var(--line); }
        .toolbar .crumb { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .toolbar .crumb a:hover { color: var(--accent); }

        .coll-empty { text-align: center; padding: 70px 24px; font-family: var(--serif); font-style: italic; font-size: 22px; color: var(--muted); }
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
                <span class="count">❧ {{ $products->total() }}</span>
                <span class="rule"></span>
                <span class="crumb">{{ __('site.storefront.shop_all.h2') }}</span>
            </div>

            @if ($products->isEmpty())
                <div class="coll-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="blooms">
                    @foreach ($products as $product)
                        @include('themes.posy._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
