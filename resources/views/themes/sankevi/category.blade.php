@php
    $title = $category->name;
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $hasActiveFilters = $filters['q'] || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock'] || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        /*
         | Sankevi category — the section of the yard. The filter rail is a
         | ruled order form standing in the margin: no panel, no shadow, just
         | hairlines and the spec voice, so the photographs stay the loudest
         | thing on the page.
         */
        .catalog { display: grid; grid-template-columns: 232px 1fr; gap: 54px; align-items: start; padding-top: 34px; }

        .filters { position: sticky; top: calc(var(--header-height) + 26px); border-top: 1px solid var(--line2); }
        .filters .fg { padding: 20px 0; border-bottom: 1px solid var(--line); }
        .filters h4 { font-size: 10.5px; font-weight: 500; letter-spacing: .24em; text-transform: uppercase; color: var(--faint); margin-bottom: 14px; }
        .filters .field { display: flex; flex-direction: column; gap: 7px; margin-bottom: 12px; }
        .filters .field:last-child { margin-bottom: 0; }
        .filters .field label { font-size: 10.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .filters input[type="search"], .filters input[type="number"], .filters select {
            width: 100%; padding: 11px 13px; background-color: var(--surface); border: 1px solid var(--line);
            color: var(--txt); font-family: var(--body); font-size: 14px; transition: border-color .25s ease;
        }
        /* background-COLOR, not the shorthand: the layout draws this
           select's chevron as a background-image, and `background:`
           would reset it — leaving a select with no arrow at all,
           since appearance:none has already removed the native one. */
        .filters select { padding-right: 36px; }
        .filters input::placeholder { color: var(--faint); }
        .filters input:focus, .filters select:focus { outline: none; border-color: var(--accent); }
        .filters .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .filters .check { display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--txt); cursor: pointer; }
        .filters .check input { width: 16px; height: 16px; accent-color: var(--accent); }
        .filters .actions { padding: 22px 0 0; display: flex; flex-direction: column; gap: 14px; }
        .filters .clear { align-self: center; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid transparent; padding-bottom: 2px; transition: color .25s ease, border-color .25s ease; }
        .filters .clear:hover { color: var(--accent-ink); border-color: currentColor; }

        /* the tally above the grid */
        .toolbar { display: flex; align-items: center; gap: 16px; margin-bottom: 26px; }
        .toolbar .count { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); font-variant-numeric: tabular-nums; }
        .toolbar .rule { flex: 1; height: 1px; background: var(--line); }

        /* decorative section chips — purely visual; real filtering is the rail */
        .spec-pills { display: flex; flex-wrap: wrap; gap: 9px; margin: 22px 0 30px; }
        .spec-pills .pill.on { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }

        .cat-empty { padding: 90px 24px; text-align: center; font-family: var(--display); font-weight: 300; letter-spacing: .012em; font-size: 24px; color: var(--muted); border: 1px solid var(--line); }

        /* the grid loses a column to the rail — three plates, not four */
        .catalog .shelf { grid-template-columns: repeat(3, 1fr); }

        @media (max-width: 1000px) {
            .catalog { grid-template-columns: 1fr; gap: 30px; }
            .filters { position: static; }
        }
        @media (max-width: 560px) {
            .cat-empty { padding: 56px 18px; font-size: 20px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head reveal">
                @if ($theme->on('gutter_index'))
                    <span class="gx" aria-hidden="true" style="top: 60px;">{{ $category->name }}</span>
                @endif
                <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ $category->name }}</div>
                <h1>{{ $category->name }}</h1>
                @if ($category->description)<p>{{ $category->description }}</p>@endif
            </div>

            <div class="spec-pills" aria-hidden="true">
                <span class="pill on">{{ $category->name }}</span>
                <span class="pill">{{ __('site.storefront.sankevi.pill_kiln') }}</span>
                <span class="pill">{{ __('site.storefront.sankevi.pill_graded') }}</span>
                <span class="pill">{{ __('site.storefront.sankevi.pill_cut') }}</span>
            </div>

            <div class="catalog">
                <aside class="filters reveal">
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
                    <div class="toolbar reveal">
                        <span class="count">{{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}</span>
                        <span class="rule"></span>
                    </div>
                    @if ($products->isEmpty())
                        <div class="cat-empty reveal">{{ __('site.storefront.no_products') }}</div>
                    @else
                        <div class="shelf {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}" style="--sheet-label: '{{ str_replace(['\\', '\''], '', $theme->label('sheet_marks')) }} '">
                            @foreach ($products as $product)
                                @include('themes.sankevi._card', ['product' => $product, 'badge' => null])
                            @endforeach
                        </div>
                        @include('storefront.partials.pagination')
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
