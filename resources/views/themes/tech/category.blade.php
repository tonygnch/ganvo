@php
    $title = $category->name;
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $hasActiveFilters = $filters['q'] || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock'] || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .page-head { padding: 44px 0 26px; border-bottom: 1px solid var(--line); }
        .page-head .crumb { font-family: var(--mono); font-size: 12px; color: var(--faint); }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--archivo); font-weight: 800; font-size: clamp(34px,4.4vw,54px); letter-spacing: -.02em; margin-top: 8px; }
        .page-head p { color: var(--muted); margin-top: 10px; max-width: 60ch; }
        .catalog { display: grid; grid-template-columns: 230px 1fr; gap: 44px; padding: 32px 0 0; align-items: start; }
        .filters .fg { margin-bottom: 22px; border-bottom: 1px solid var(--line); padding-bottom: 20px; }
        .filters h4 { font-family: var(--mono); font-size: 12px; color: var(--muted); margin-bottom: 12px; }
        .filters input, .filters select { width: 100%; background: var(--bg); border: 1px solid var(--line); border-radius: 7px; padding: 11px 12px; color: var(--txt); font-family: var(--mono); font-size: 13px; }
        .filters input:focus, .filters select:focus { outline: none; border-color: var(--accent); }
        .filters .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .filters .check { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--muted); cursor: pointer; }
        .filters .check input { width: auto; accent-color: var(--accent); }
        .filters .actions { display: flex; flex-direction: column; gap: 10px; }
        .filters .clear { font-family: var(--mono); font-size: 11px; color: var(--faint); text-align: center; }
        .filters .clear:hover { color: var(--accent); }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; font-family: var(--mono); font-size: 12px; color: var(--faint); }
        .empty { border: 1px solid var(--line); border-radius: 12px; padding: 60px; text-align: center; color: var(--muted); font-family: var(--mono); font-size: 13px; }
        @media (max-width: 1000px) { .catalog { grid-template-columns: 1fr; gap: 28px; } }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head rv">
                <div class="crumb">/ <a href="/">home</a> / {{ strtolower($category->name) }}</div>
                <h1>{{ $category->name }}</h1>
                @if ($category->description)<p>{{ $category->description }}</p>@endif
            </div>
            <div class="catalog">
                <aside class="filters rv">
                    <form method="get" action="/categories/{{ $category->slug }}">
                        <div class="fg"><h4>// {{ __('site.storefront.controls.search') }}</h4>
                            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('site.storefront.controls.search_placeholder') }}"></div>
                        <div class="fg"><h4>// {{ __('site.storefront.controls.sort') }}</h4>
                            <select name="sort">
                                <option value="newest" @selected($filters['sort']==='newest')>{{ __('site.storefront.controls.sort_newest') }}</option>
                                <option value="price_asc" @selected($filters['sort']==='price_asc')>{{ __('site.storefront.controls.sort_price_asc') }}</option>
                                <option value="price_desc" @selected($filters['sort']==='price_desc')>{{ __('site.storefront.controls.sort_price_desc') }}</option>
                                <option value="name_asc" @selected($filters['sort']==='name_asc')>{{ __('site.storefront.controls.sort_name_asc') }}</option>
                            </select></div>
                        <div class="fg"><h4>// {{ __('site.storefront.controls.price') }}</h4>
                            <div class="price-row">
                                <input type="number" name="min_price" step="0.01" min="0" value="{{ $minPriceInput }}" placeholder="0">
                                <input type="number" name="max_price" step="0.01" min="0" value="{{ $maxPriceInput }}" placeholder="∞">
                            </div></div>
                        <div class="fg" style="border:none"><h4>// {{ __('site.storefront.controls.availability') }}</h4>
                            <label class="check"><input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])> {{ __('site.storefront.controls.in_stock_only') }}</label></div>
                        <div class="actions">
                            <button type="submit" class="btn">{{ __('site.storefront.controls.apply') }}</button>
                            @if ($hasActiveFilters)<a class="clear" href="/categories/{{ $category->slug }}">{{ __('site.storefront.controls.clear') }}</a>@endif
                        </div>
                    </form>
                </aside>
                <div>
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
            </div>
        </div>
    </main>
@endsection
