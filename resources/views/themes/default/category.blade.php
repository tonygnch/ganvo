@php
    $title = $category->name;

    // Convert min/max from cents (controller side) back into major units
    // for the form inputs. Blank when null so the placeholder shows.
    $minPriceInput = $filters['min_price'] !== null
        ? number_format($filters['min_price'] / 100, 2, '.', '')
        : '';
    $maxPriceInput = $filters['max_price'] !== null
        ? number_format($filters['max_price'] / 100, 2, '.', '')
        : '';
    $hasActiveFilters = $filters['q']
        || $filters['min_price'] !== null
        || $filters['max_price'] !== null
        || $filters['in_stock']
        || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.default.layout')

@section('content')
    <style>
        /* ===== CATALOG (category page) ===== */
        .catalog { display: grid; grid-template-columns: 220px 1fr; gap: 50px; padding: 34px 0 0; align-items: start; }

        /* sidebar filters */
        .filters .fg { margin-bottom: 28px; }
        .filters h4 { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--ink); font-weight: 600; }
        .filters .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .filters .field label { font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); font-weight: 600; }
        .filters input[type="text"], .filters input[type="search"], .filters input[type="number"], .filters select {
            padding: 11px 12px; background: #fff; border: 1px solid var(--line); border-radius: 0;
            font-family: var(--body); font-size: 13px; color: var(--ink); transition: border-color .15s ease; width: 100%;
        }
        .filters input:focus, .filters select:focus { outline: none; border-color: var(--ink); }
        .filters .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .filters .check { display: flex; align-items: center; gap: 9px; font-size: 13px; cursor: pointer; }
        .filters .check input { accent-color: var(--accent); }
        .filters .actions { display: flex; flex-direction: column; gap: 10px; margin-top: 26px; }
        .filters .actions .btn { font-size: 11px; padding: 14px 20px; }
        .filters .clear { font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); text-align: center; margin-top: 4px; transition: color .15s ease; }
        .filters .clear:hover { color: var(--ink); }

        /* toolbar */
        .toolbar { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid var(--rule); margin-bottom: 28px; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); gap: 12px; flex-wrap: wrap; }
        .toolbar .count strong { color: var(--ink); }

        .cat-empty { text-align: center; padding: 80px 20px; color: var(--muted); border: 1px solid var(--ink); }
        .cat-empty p { font-size: 13px; letter-spacing: .14em; text-transform: uppercase; }

        @media (max-width: 1000px) {
            .catalog { grid-template-columns: 1fr; gap: 30px; }
            .filters { border: 1px solid var(--ink); padding: 20px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="ed-head rv">
                <div>
                    <div class="crumb">
                        <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / <span>{{ $category->name }}</span>
                    </div>
                    <h1>{{ $category->name }}</h1>
                </div>
                @if ($category->description)
                    <div class="meta">{{ $category->description }}</div>
                @endif
            </div>

            <div class="catalog">
                <aside class="filters rv">
                    <form method="get" action="/categories/{{ $category->slug }}">
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.search') }}</h4>
                            <div class="field">
                                <input type="search" name="q" value="{{ $filters['q'] }}"
                                       placeholder="{{ __('site.storefront.controls.search_placeholder') }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.sort') }}</h4>
                            <div class="field">
                                <select name="sort">
                                    <option value="newest"     @selected($filters['sort'] === 'newest')>{{ __('site.storefront.controls.sort_newest') }}</option>
                                    <option value="price_asc"  @selected($filters['sort'] === 'price_asc')>{{ __('site.storefront.controls.sort_price_asc') }}</option>
                                    <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('site.storefront.controls.sort_price_desc') }}</option>
                                    <option value="name_asc"   @selected($filters['sort'] === 'name_asc')>{{ __('site.storefront.controls.sort_name_asc') }}</option>
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
                            <button type="submit" class="btn red">{{ __('site.storefront.controls.apply') }}</button>
                            @if ($hasActiveFilters)
                                <a class="clear" href="/categories/{{ $category->slug }}">{{ __('site.storefront.controls.clear') }}</a>
                            @endif
                        </div>
                    </form>
                </aside>

                <div>
                    <div class="toolbar">
                        <span class="count">
                            {{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}
                        </span>
                    </div>

                    @if ($products->isEmpty())
                        <div class="cat-empty">
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
            </div>
        </div>
    </main>
@endsection
