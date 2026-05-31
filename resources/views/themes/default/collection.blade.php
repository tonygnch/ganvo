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
        .coll-hero {
            position: relative;
            height: 360px;
            overflow: hidden;
            background: var(--soft);
            margin-bottom: 0;
        }
        .coll-hero img {
            width: 100%; height: 100%; object-fit: cover;
            display: block;
        }
        /* Centered overlay text reads cleanly via a dark vignette instead
           of mix-blend-mode (which gives unpredictable color on busy photos). */
        .coll-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.55));
            pointer-events: none;
        }
        .coll-hero .overlay {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            text-align: center;
            color: #fff;
            padding: 0 40px;
            z-index: 2;
            text-shadow: 0 2px 24px rgba(0, 0, 0, .35);
        }
        .coll-hero .overlay .sub {
            font-size: 11px;
            letter-spacing: .22em;
            text-transform: uppercase;
            margin-bottom: 14px;
            opacity: .9;
        }
        .coll-hero .overlay h1 {
            font-family: var(--display);
            font-size: clamp(44px, 6vw, 88px);
            font-weight: 500;
            line-height: 1;
            color: #fff;
        }
        .coll-hero .overlay p {
            font-size: 14px;
            max-width: 52ch;
            margin-top: 14px;
            color: rgba(255, 255, 255, .9);
        }

        .coll-head-noimg {
            padding: 50px 0 34px;
            border-bottom: 1px solid var(--line);
            text-align: center;
        }
        .coll-head-noimg h1 {
            font-family: var(--display);
            font-size: clamp(40px, 5vw, 64px);
            font-weight: 500;
        }
        .coll-head-noimg p {
            color: var(--muted);
            margin-top: 14px;
            max-width: 56ch;
            margin-left: auto;
            margin-right: auto;
            font-size: 15px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 24px;
            font-size: 13px;
            color: var(--muted);
            gap: 12px;
            flex-wrap: wrap;
        }
        .toolbar .count strong { color: var(--ink); }

        @media (max-width: 720px) {
            .coll-hero { height: 280px; }
        }
    </style>

    <main>
        @if ($bannerUrl)
            <section class="coll-hero rv">
                <img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">
                <div class="overlay">
                    <div>
                        <div class="sub">{{ __('site.storefront.featured.eyebrow') }}</div>
                        <h1>{{ $collection->title }}</h1>
                        @if ($collection->description)
                            <p>{{ $collection->description }}</p>
                        @endif
                    </div>
                </div>
            </section>
        @else
            <div class="wrap">
                <div class="coll-head-noimg rv">
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)
                        <p>{{ $collection->description }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="wrap">
            <div class="toolbar">
                <span class="count">
                    <strong>{{ $products->total() }}</strong> {{ __('site.storefront.shop_all.h2') }}
                </span>
            </div>

            @if ($products->isEmpty())
                <div style="text-align:center; padding: 80px 20px; color: var(--muted); border: 1px solid var(--line);">
                    <p style="font-size: 13px; letter-spacing: .14em; text-transform: uppercase;">{{ __('site.storefront.no_products') }}</p>
                </div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        <a class="pcard rv" href="/products/{{ $product->slug }}">
                            <div class="img ph">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                                @else
                                    <span>{{ $product->name }}</span>
                                @endif
                            </div>
                            <div class="nm">{{ $product->name }}</div>
                            <div class="pr">@money($product->price_cents)</div>
                        </a>
                    @endforeach
                </div>

                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
