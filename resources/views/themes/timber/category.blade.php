@php
    $title = $category->name;
    $minPriceInput = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceInput = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $hasActiveFilters = $filters['q'] || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock'] || ($filters['sort'] ?? 'newest') !== 'newest';
@endphp
@extends('themes.timber.layout')

@section('content')
    <style>
        /*
         | Timber category — the trade counter's order form. A spec-sheet filter
         | rail (the clipboard on the counter, ruled along its top edge) stands
         | beside the rack: price-list cards stamped with lot numbers. The tally
         | line above the grid says how much of this section the yard is holding.
         */
        .catalog { display: grid; grid-template-columns: 260px 1fr; gap: 40px; align-items: start; padding-top: 8px; }

        /* decorative spec pills (design accent — real filtering lives in the rail) */
        .spec-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: 20px 0 30px; }
        .spec-pills span {
            font-family: var(--mono); font-size: 10.5px; letter-spacing: .04em; text-transform: uppercase;
            color: var(--muted); background: var(--surface); border: 1px solid var(--line2);
            padding: 6px 14px; border-radius: 5px; box-shadow: 0 2px 0 0 var(--line);
        }
        .spec-pills span.on { color: var(--on-accent); background: var(--accent); border-color: var(--accent-deep); box-shadow: 0 2px 0 0 var(--accent-deep); }

        /* filter rail — the clipboard: light panel, hard shadow, ruler edge */
        .filters {
            background: var(--surface); border: 1px solid var(--line); border-radius: 8px;
            box-shadow: 0 2px 0 0 var(--line); overflow: hidden; position: relative;
        }
        .filters .rule-ticks { margin-bottom: -8px; }
        .filters .fg { padding: 18px 20px; border-bottom: 1px solid var(--line); }
        .filters .fg:last-of-type { border-bottom: none; }
        .filters h4 {
            font-family: var(--mono); font-size: 11px; font-weight: 500; letter-spacing: .12em;
            text-transform: uppercase; color: var(--faint); margin-bottom: 12px;
        }
        .filters .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
        .filters .field:last-child { margin-bottom: 0; }
        .filters .field label {
            font-family: var(--mono); font-size: 11px; letter-spacing: .06em;
            text-transform: uppercase; color: var(--muted);
        }
        .filters input[type="search"],
        .filters input[type="number"],
        .filters select {
            padding: 10px 12px; background: var(--bg); border: 1px solid var(--line2);
            border-radius: 5px; font-family: var(--body); font-size: 14px; color: var(--txt); width: 100%;
            transition: border-color .2s ease;
        }
        .filters input:focus, .filters select:focus { outline: none; border-color: var(--accent); }
        .filters .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .filters .check { display: flex; align-items: center; gap: 9px; font-size: 14px; color: var(--txt); cursor: pointer; }
        .filters .check input { width: 17px; height: 17px; accent-color: var(--accent); }
        .filters .actions { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; background: var(--surface2); border-top: 1px solid var(--line); }
        .filters .clear {
            text-align: center; font-family: var(--mono); font-size: 12px; text-transform: uppercase;
            letter-spacing: .04em; color: var(--muted);
            border-bottom: 1px solid transparent; align-self: center; padding-bottom: 1px;
            transition: color .2s ease, border-color .2s ease;
        }
        .filters .clear:hover { color: var(--accent-deep); border-color: currentColor; }

        /* result count — the tally scribbled at the head of the cutting list */
        .toolbar {
            font-family: var(--mono); font-size: 12px; letter-spacing: .06em; text-transform: uppercase;
            color: var(--muted); margin: 0 0 22px; padding-bottom: 12px; border-bottom: 1px solid var(--line);
            display: flex; align-items: center; gap: 10px;
        }
        .toolbar::before { content: "▮"; color: var(--accent); }

        .cat-empty {
            background: var(--surface); border: 1px solid var(--line); border-radius: 8px;
            box-shadow: 0 2px 0 0 var(--line);
            padding: 70px 24px; text-align: center;
            font-family: var(--display); font-weight: 700; text-transform: uppercase;
            letter-spacing: .01em; font-size: 24px; color: var(--muted);
        }

        @media (max-width: 1000px) { .catalog { grid-template-columns: 1fr; } }
        @media (max-width: 680px) {
            .catalog { gap: 26px; }
            .filters .fg, .filters .actions { padding: 16px; }
            .spec-pills { gap: 8px; margin: 16px 0 24px; }
            .cat-empty { padding: 48px 18px; font-size: 20px; }
        }
    </style>

    <main>
        <div class="wrap">
            <div class="page-head reveal">
                <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a> / {{ $category->name }}</div>
                <h1>{{ $category->name }}</h1>
                @if ($category->description)<p>{{ $category->description }}</p>@endif
            </div>

            {{-- Decorative spec pills — purely visual; real filtering lives in the rail. --}}
            <div class="spec-pills" aria-hidden="true">
                <span class="on">{{ $category->name }}</span>
                <span>{{ __('site.storefront.timber.pill_treated') }}</span>
                <span>{{ __('site.storefront.timber.pill_graded') }}</span>
                <span>{{ __('site.storefront.timber.pill_cut') }}</span>
            </div>

            <div class="catalog">
                <aside class="filters reveal">
                    @if ($theme->on('ruler'))<div class="rule-ticks" aria-hidden="true"></div>@endif
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
                        <div class="racks {{ $theme->on('lot_stamps') ? '' : 'no-lot' }}" style="--lot-label: '{{ str_replace(['\\', '\''], '', $theme->label('lot_stamps')) }} '">
                            @foreach ($products as $product)
                                @include('themes.timber._card', ['product' => $product, 'badge' => null])
                            @endforeach
                        </div>
                        @include('storefront.partials.pagination')
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
