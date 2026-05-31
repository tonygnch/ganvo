@php
    $title = $collection->title;
    $bannerUrl = $collection->banner_path ? \Illuminate\Support\Facades\Storage::url($collection->banner_path) : null;
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .coll-hero { position: relative; margin-top: 24px; height: 300px; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; background: var(--surface2); display: flex; align-items: flex-end; padding: 40px; }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .5; }
        .coll-hero .inner { position: relative; z-index: 1; }
        .coll-hero .tag { font-family: var(--mono); font-size: 12px; color: var(--accent); }
        .coll-hero h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(34px,4.6vw,58px); letter-spacing: -.02em; margin-top: 8px; }
        .coll-hero p { color: var(--muted); margin-top: 8px; max-width: 56ch; }
        .toolbar { display: flex; justify-content: space-between; margin: 30px 0 20px; font-family: var(--mono); font-size: 12px; color: var(--faint); }
        .empty { border: 1px solid var(--line); border-radius: 12px; padding: 60px; text-align: center; color: var(--muted); font-family: var(--mono); font-size: 13px; }
    </style>

    <main>
        <div class="wrap">
            <div class="coll-hero rv">
                @if ($bannerUrl)<img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">@endif
                <div class="inner">
                    <div class="tag">// {{ __('site.storefront.featured.eyebrow') }}</div>
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                </div>
            </div>
            <div class="toolbar"><span>{{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}</span></div>
            @if ($products->isEmpty())
                <div class="empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">
                    @foreach ($products as $product)
                        @include('themes.tech._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
