@php
    $title = $category->name;
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $hasActiveFilters = $filters['q'] || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock'] || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .page-head { text-align: center; padding: 46px 0 24px; }
        .page-head .crumb { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--display); font-size: clamp(40px,5vw,62px); margin-top: 8px; }
        .page-head p { color: var(--muted); max-width: 46ch; margin: 10px auto 0; }
        .filterbar { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; align-items: center; margin: 24px 0 40px; }
        .filterbar input, .filterbar select { border: 1.5px solid var(--line); background: var(--card); border-radius: 99px; padding: 11px 18px; font-family: inherit; font-size: 13px; color: var(--ink); }
        .filterbar input:focus, .filterbar select:focus { outline: none; border-color: var(--accent); }
        .filterbar .price { width: 90px; }
        .filterbar .chk { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); }
        .filterbar .chk input { accent-color: var(--accent); }
        .filterbar .clear { font-size: 12px; color: var(--muted); }
        .empty { text-align: center; padding: 60px; color: var(--muted); }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head rv">
                <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ $category->name }}</div>
                <h1>{{ $category->name }}</h1>
                @if ($category->description)<p>{{ $category->description }}</p>@endif
            </div>
            <form method="get" action="/categories/{{ $category->slug }}" class="filterbar rv">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('site.storefront.controls.search_placeholder') }}">
                <select name="sort">
                    <option value="newest" @selected($filters['sort']==='newest')>{{ __('site.storefront.controls.sort_newest') }}</option>
                    <option value="price_asc" @selected($filters['sort']==='price_asc')>{{ __('site.storefront.controls.sort_price_asc') }}</option>
                    <option value="price_desc" @selected($filters['sort']==='price_desc')>{{ __('site.storefront.controls.sort_price_desc') }}</option>
                    <option value="name_asc" @selected($filters['sort']==='name_asc')>{{ __('site.storefront.controls.sort_name_asc') }}</option>
                </select>
                <input class="price" type="number" name="min_price" step="0.01" min="0" value="{{ $minPriceInput }}" placeholder="{{ __('site.storefront.controls.price_min') }}">
                <input class="price" type="number" name="max_price" step="0.01" min="0" value="{{ $maxPriceInput }}" placeholder="{{ __('site.storefront.controls.price_max') }}">
                <label class="chk"><input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])> {{ __('site.storefront.controls.in_stock_only') }}</label>
                <button type="submit" class="btn" style="padding:11px 22px">{{ __('site.storefront.controls.apply') }}</button>
                @if ($hasActiveFilters)<a class="clear" href="/categories/{{ $category->slug }}">{{ __('site.storefront.controls.clear') }}</a>@endif
            </form>
            @if ($products->isEmpty())
                <div class="empty">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="pgrid">@foreach ($products as $product)@include('themes.minimal._card')@endforeach</div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
