@php
    $title = $collection->title;
@endphp
@extends('themes.kiln.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        /* ===== Collection banner ===== */
        .coll-hero { position: relative; border-radius: 2px; overflow: hidden; margin-top: 32px; min-height: 320px; display: flex; align-items: flex-end; background: var(--deep); }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .72; }
        .coll-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(38, 36, 31, .78), rgba(38, 36, 31, .12) 60%, transparent); z-index: 1; }
        .coll-hero .overlay { position: relative; z-index: 2; padding: 44px 40px; color: #fff; max-width: 640px; }
        .coll-hero .overlay .kicker { color: var(--soft); display: block; margin-bottom: 12px; }
        .coll-hero .overlay h1 { font-family: var(--serif); font-weight: 400; font-size: clamp(40px, 5vw, 64px); line-height: 1; letter-spacing: -.01em; }
        .coll-hero .overlay h1 em { font-style: italic; color: var(--soft); }
        .coll-hero .overlay p { font-family: var(--serif); font-style: italic; font-size: 18px; max-width: 52ch; margin-top: 12px; color: rgba(255, 255, 255, .9); }

        /* count toolbar */
        .toolbar { display: flex; align-items: center; gap: 14px; margin: 32px 0 8px; }
        .toolbar .count { font-family: var(--serif); font-style: italic; font-size: 18px; color: var(--accent); }
        .toolbar .rule { flex: 1; height: 1px; background: var(--line); }
        .toolbar .crumb { font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
        .toolbar .crumb a:hover { color: var(--accent); }

        .coll-empty { text-align: center; padding: 70px 24px; font-family: var(--serif); font-style: italic; font-size: 22px; color: var(--muted); }
    </style>

    <main>
        <div class="wrap">
            @if ($bannerUrl)
                <section class="coll-hero reveal">
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
                <span class="count">{{ $products->total() }}</span>
                <span class="rule"></span>
                <span class="crumb">{{ __('site.storefront.shop_all.h2') }}</span>
            </div>

            @if ($products->isEmpty())
                <div class="coll-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="blooms">
                    @foreach ($products as $product)
                        @include('themes.kiln._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
