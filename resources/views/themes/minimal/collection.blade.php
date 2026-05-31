@php
    $title = $collection->title;
    $bannerUrl = $collection->banner_path ? \Illuminate\Support\Facades\Storage::url($collection->banner_path) : null;
@endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .coll-hero { position: relative; margin-top: 14px; height: 320px; border-radius: 34px; overflow: hidden; background: linear-gradient(120deg,#f6dccd,#f1d2c3); display: grid; place-items: center; text-align: center; padding: 40px; }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .coll-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(rgba(90,63,53,.25), rgba(90,63,53,.4)); }
        .coll-hero .inner { position: relative; z-index: 1; color: #fff; text-shadow: 0 2px 18px rgba(0,0,0,.3); }
        .coll-hero .k { letter-spacing: .18em; text-transform: uppercase; font-size: 12px; }
        .coll-hero h1 { font-family: var(--display); font-size: clamp(38px,5vw,62px); margin-top: 8px; }
        .coll-hero p { max-width: 56ch; margin: 10px auto 0; }
        .coll-hero.noimg { color: var(--ink); } .coll-hero.noimg::after { display: none; } .coll-hero.noimg .inner { color: var(--ink); text-shadow: none; }
        .coll-hero.noimg .k { color: var(--accent); }
        .toolbar { text-align: center; margin: 30px 0 24px; font-size: 13px; color: var(--muted); }
        .empty { text-align: center; padding: 60px; color: var(--muted); }
    </style>

    <main>
        <div class="wrap">
            <div class="coll-hero rv {{ $bannerUrl ? '' : 'noimg' }}">
                @if ($bannerUrl)<img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">@endif
                <div class="inner">
                    <div class="k">{{ __('site.storefront.featured.eyebrow') }}</div>
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                </div>
            </div>
            <div class="toolbar rv">{{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}</div>
            @if ($products->isEmpty())
                <div class="empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">@foreach ($products as $product)@include('themes.minimal._card')@endforeach</div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
