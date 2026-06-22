@php
    $title = $category->name;
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $hasActiveFilters = $filters['q'] || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock'] || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.brick.layout')

@section('content')
    <style>
        .catalog { display: grid; grid-template-columns: 240px 1fr; gap: 30px; align-items: start; }
        .filters { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); }
        .filters .fg { padding: 18px 18px; border-bottom: 2.5px solid var(--ink); }
        .filters .fg:last-child { border-bottom: none; }
        .filters h4 { font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 12px; }
        .filters .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
        .filters .field:last-child { margin-bottom: 0; }
        .filters .field label { font-family: var(--display); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .filters input[type="search"], .filters input[type="number"], .filters select { padding: 10px 11px; background: #fff; border: 2.5px solid var(--ink); font-family: var(--body); font-size: 13px; color: var(--ink); width: 100%; }
        .filters input:focus, .filters select:focus { outline: none; box-shadow: var(--pop-sm); }
        .filters .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .filters .check { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .filters .check input { width: 18px; height: 18px; accent-color: var(--ink); }
        .filters .actions { padding: 18px; display: flex; flex-direction: column; gap: 10px; }
        .filters .clear { font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: center; }
        .filters .clear:hover { color: var(--accent); background: var(--ink); padding: 4px; }

        .toolbar { border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); background: var(--accent); padding: 14px 18px; margin-bottom: 26px; font-family: var(--display); font-size: 13px; font-weight: 800; text-transform: uppercase; }
        .cat-empty { border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 60px 24px; text-align: center; font-family: var(--display); font-weight: 800; text-transform: uppercase; }

        @media (max-width: 1000px) { .catalog { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap">
            <div class="ed-head rv">
                <div>
                    <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ $category->name }}</div>
                    <h1>{{ $category->name }}</h1>
                </div>
                @if ($category->description)<div class="meta">{{ $category->description }}</div>@endif
            </div>

            <div class="catalog">
                <aside class="filters rv">
                    <form method="get" action="/categories/{{ $category->slug }}">
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.search') }}</h4>
                            <div class="field">
                                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('site.storefront.controls.search_placeholder') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.sort') }}</h4>
                            <div class="field">
                                <select name="sort">
                                    <option value="newest" @selected($filters['sort'] === 'newest')>{{ __('site.storefront.controls.sort_newest') }}</option>
                                    <option value="price_asc" @selected($filters['sort'] === 'price_asc')>{{ __('site.storefront.controls.sort_price_asc') }}</option>
                                    <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('site.storefront.controls.sort_price_desc') }}</option>
                                    <option value="name_asc" @selected($filters['sort'] === 'name_asc')>{{ __('site.storefront.controls.sort_name_asc') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.price') }}</h4>
                            <div class="price-row">
                                <div class="field">
                                    <label for="min_price">{{ __('site.storefront.controls.price_min') }}</label>
                                    <input type="number" id="min_price" name="min_price" step="0.01" min="0" value="{{ $minPriceInput }}" placeholder="0">
                                </div>
                                <div class="field">
                                    <label for="max_price">{{ __('site.storefront.controls.price_max') }}</label>
                                    <input type="number" id="max_price" name="max_price" step="0.01" min="0" value="{{ $maxPriceInput }}" placeholder="∞">
                                </div>
                            </div>
                        </div>
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.availability') }}</h4>
                            <label class="check">
                                <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])>
                                <span>{{ __('site.storefront.controls.in_stock_only') }}</span>
                            </label>
                        </div>
                        <div class="actions">
                            <button type="submit" class="btn accent">{{ __('site.storefront.controls.apply') }}</button>
                            @if ($hasActiveFilters)
                                <a class="clear" href="/categories/{{ $category->slug }}">{{ __('site.storefront.controls.clear') }}</a>
                            @endif
                        </div>
                    </form>
                </aside>

                <div>
                    <div class="toolbar">
                        {{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}
                    </div>
                    @if ($products->isEmpty())
                        <div class="cat-empty">{{ __('site.storefront.no_products') }}</div>
                    @else
                        <div class="pgrid">
                            @foreach ($products as $product)
                                @include('themes.brick._card', ['product' => $product, 'badge' => null])
                            @endforeach
                        </div>
                        @include('storefront.partials.pagination')
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
