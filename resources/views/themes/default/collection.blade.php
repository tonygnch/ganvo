@php
    $title = $collection->title;
@endphp
@extends('themes.default.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        .coll-hero { position: relative; height: 380px; overflow: hidden; background: var(--ink); }
        .coll-hero img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .coll-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(8,8,8,.6), rgba(8,8,8,.2)); pointer-events: none; }
        .coll-hero .overlay {
            position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end;
            padding: 0 36px 7vh; max-width: 1320px; margin: 0 auto; left: 0; right: 0; color: var(--paper); z-index: 2;
        }
        .coll-hero .overlay .sub { font-size: 11px; letter-spacing: .22em; text-transform: uppercase; margin-bottom: 14px; opacity: .9; }
        .coll-hero .overlay h1 { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: clamp(40px, 6vw, 92px); line-height: .9; letter-spacing: -.02em; }
        .coll-hero .overlay p { font-size: 14px; max-width: 52ch; margin-top: 14px; color: rgba(255,255,255,.9); }

        .toolbar { display: flex; justify-content: space-between; align-items: center; padding: 22px 0; border-bottom: 1px solid var(--rule); margin: 30px 0 28px; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); gap: 12px; flex-wrap: wrap; }
        .toolbar .count strong { color: var(--ink); }

        .coll-empty { text-align: center; padding: 80px 20px; color: var(--muted); border: 1px solid var(--ink); }
        .coll-empty p { font-size: 13px; letter-spacing: .14em; text-transform: uppercase; }

        @media (max-width: 680px) { .coll-hero { height: 300px; } }
    </style>

    <main>
        @if ($bannerUrl)
            <section class="coll-hero rv">
                <img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">
                <div class="overlay">
                    <div class="sub">{{ __('site.storefront.featured.eyebrow') }}</div>
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)
                        <p>{{ $collection->description }}</p>
                    @endif
                </div>
            </section>
        @else
            <div class="wrap">
                <div class="ed-head rv">
                    <div>
                        <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a></div>
                        <h1>{{ $collection->title }}</h1>
                    </div>
                    @if ($collection->description)
                        <div class="meta">{{ $collection->description }}</div>
                    @endif
                </div>
            </div>
        @endif

        <div class="wrap">
            <div class="toolbar">
                <span class="count"><strong>{{ $products->total() }}</strong> {{ __('site.storefront.shop_all.h2') }}</span>
            </div>

            @if ($products->isEmpty())
                <div class="coll-empty">
                    <p>{{ __('site.storefront.no_products') }}</p>
                </div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        @include('themes.default._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>

                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
