@php
    $title = $category->name;
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $hasActiveFilters = $filters['q'] || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock'] || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.ember.layout')

@section('content')
    <style>
        /* ===== Category — bordered filter rail + blend tile grid ===== */
        .catalog { display: grid; grid-template-columns: 250px 1fr; gap: 44px; align-items: start; padding-top: 8px; }

        /* decorative roast pills (design accent — not functional) */
        .pills { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: 6px 0 30px; }
        .pills span {
            font-family: var(--mono); font-size: 12px; text-transform: uppercase; letter-spacing: .02em;
            color: var(--muted); background: var(--card); border: 1.5px solid var(--line);
            padding: 8px 18px; border-radius: 2px;
        }
        .pills span.on { color: var(--bg); background: var(--ink); border-color: var(--ink); }

        /* filter rail */
        .filters {
            background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px;
            overflow: hidden; position: relative;
            box-shadow: 6px 6px 0 var(--soft2);
        }
        .filters .fg { padding: 18px 20px; border-bottom: 1px solid var(--rule); }
        .filters .fg:last-of-type { border-bottom: none; }
        .filters h4 {
            font-family: var(--mono); font-size: 12px; letter-spacing: .04em;
            text-transform: uppercase; color: var(--ink); margin-bottom: 12px;
        }
        .filters h4::before { content: "// "; color: var(--accent); }
        .filters .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .filters .field:last-child { margin-bottom: 0; }
        .filters .field label {
            font-family: var(--mono); font-size: 11px; letter-spacing: .04em;
            text-transform: uppercase; color: var(--muted);
        }
        .filters input[type="search"],
        .filters input[type="number"],
        .filters select {
            padding: 10px 12px; background: var(--card); border: 1.5px solid var(--line);
            border-radius: 2px; font-family: var(--body); font-size: 14px; color: var(--ink); width: 100%;
            transition: border-color .2s ease;
        }
        .filters input:focus, .filters select:focus { outline: none; border-color: var(--ink); }
        .filters .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .filters .check { display: flex; align-items: center; gap: 9px; font-size: 14px; color: var(--ink); cursor: pointer; }
        .filters .check input { width: 17px; height: 17px; accent-color: var(--accent); }
        .filters .actions { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; border-top: 1.5px solid var(--ink); }
        .filters .clear {
            text-align: center; font-family: var(--mono); font-size: 11px; text-transform: uppercase; color: var(--muted);
            border-bottom: 1px solid transparent; align-self: center; padding-bottom: 1px;
            transition: color .2s ease, border-color .2s ease;
        }
        .filters .clear:hover { color: var(--accent); border-color: currentColor; }

        /* result count line — mono caps */
        .toolbar {
            font-family: var(--mono); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted);
            margin: 0 0 22px; display: flex; align-items: center; gap: 10px;
        }
        .toolbar::before { content: "//"; color: var(--accent); }

        .cat-empty {
            background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px;
            padding: 70px 24px; text-align: center;
            font-family: var(--display); font-style: italic; font-size: 22px; color: var(--muted);
        }

        @media (max-width: 1000px) { .catalog { grid-template-columns: 1fr; } }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head reveal">
                <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ $category->name }}</div>
                <h1>{{ $category->name }}</h1>
                @if ($category->description)<p>{{ $category->description }}</p>@endif
            </div>

            {{-- Decorative roast pills — purely visual; real filtering lives in the rail. --}}
            <div class="pills" aria-hidden="true">
                <span class="on">{{ $category->name }}</span>
                <span>{{ __('site.storefront.ember.pill_origin') }}</span>
                <span>{{ __('site.storefront.ember.pill_whole_bean') }}</span>
                <span>{{ __('site.storefront.ember.pill_small_batch') }}</span>
            </div>

            <div class="catalog">
                <aside class="filters reveal">
                    <div class="tape"></div>
                    <form method="get" action="/categories/{{ $category->slug }}">
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.search') }}</h4>
                            <div class="field">
                                <label class="sr-only" for="q">{{ __('site.storefront.controls.search') }}</label>
                                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('site.storefront.controls.search_placeholder') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="fg">
                            <h4>{{ __('site.storefront.controls.sort') }}</h4>
                            <div class="field">
                                <label class="sr-only" for="sort">{{ __('site.storefront.controls.sort') }}</label>
                                <select id="sort" name="sort">
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
                            <button type="submit" class="btn block">{{ __('site.storefront.controls.apply') }}</button>
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
                        <div class="blooms">
                            @foreach ($products as $product)
                                @include('themes.ember._card', ['product' => $product, 'badge' => null])
                            @endforeach
                        </div>
                        @include('storefront.partials.pagination')
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
