@php
    /*
     | Catalog controls toolbar: search + sort + category filter + price
     | range + in-stock toggle. Themes include this from index.blade.php
     | above their product grid.
     |
     | Inputs (from controller):
     |   $filters     associative array (see StorefrontController::extractFilters)
     |   $categories  collection of root categories for the dropdown
     |   $products    paginator (for the result count display)
     |
     | Submits as GET to "/" so URLs are shareable + bookmarkable. Form
     | elements all share names with $filters keys so refresh keeps state.
     |
     | Styled with .cat-* prefixed classes so themes can override or
     | wrap without conflict.
     */
    $hasActiveFilters = $filters['q']
        || $filters['category']
        || $filters['min_price'] !== null
        || $filters['max_price'] !== null
        || $filters['in_stock']
        || ($filters['sort'] ?? 'newest') !== 'newest';
    // For price input display, convert cents back to major units.
    $minPriceDisplay = $filters['min_price'] !== null ? number_format($filters['min_price'] / 100, 2, '.', '') : '';
    $maxPriceDisplay = $filters['max_price'] !== null ? number_format($filters['max_price'] / 100, 2, '.', '') : '';
    $totalCount = method_exists($products, 'total') ? $products->total() : $products->count();
@endphp

<div class="cat-controls">
    <form method="get" action="/" class="cat-form" role="search">
        <div class="cat-row cat-row-primary">
            {{-- Search box. Top-left, most prominent — it's the primary
                 interaction most shoppers reach for first. --}}
            <label class="cat-field cat-field-search">
                <span class="cat-label">{{ __('site.storefront.controls.search') }}</span>
                <input type="search"
                       name="q"
                       value="{{ $filters['q'] }}"
                       placeholder="{{ __('site.storefront.controls.search_placeholder') }}"
                       autocomplete="off">
            </label>

            <label class="cat-field">
                <span class="cat-label">{{ __('site.storefront.controls.sort') }}</span>
                <select name="sort">
                    <option value="newest"     @selected($filters['sort'] === 'newest')>{{ __('site.storefront.controls.sort_newest') }}</option>
                    <option value="price_asc"  @selected($filters['sort'] === 'price_asc')>{{ __('site.storefront.controls.sort_price_asc') }}</option>
                    <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('site.storefront.controls.sort_price_desc') }}</option>
                    <option value="name_asc"   @selected($filters['sort'] === 'name_asc')>{{ __('site.storefront.controls.sort_name_asc') }}</option>
                </select>
            </label>

            @if ($categories->isNotEmpty())
                <label class="cat-field">
                    <span class="cat-label">{{ __('site.storefront.controls.category') }}</span>
                    <select name="category">
                        <option value="">{{ __('site.storefront.controls.category_all') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->slug }}" @selected($filters['category'] === $cat->slug)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
        </div>

        <div class="cat-row cat-row-secondary">
            <label class="cat-field cat-field-price">
                <span class="cat-label">{{ __('site.storefront.controls.price_min') }}</span>
                <input type="number" name="min_price" step="0.01" min="0" value="{{ $minPriceDisplay }}" placeholder="0">
            </label>
            <label class="cat-field cat-field-price">
                <span class="cat-label">{{ __('site.storefront.controls.price_max') }}</span>
                <input type="number" name="max_price" step="0.01" min="0" value="{{ $maxPriceDisplay }}" placeholder="∞">
            </label>
            <label class="cat-field cat-field-checkbox">
                <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])>
                <span>{{ __('site.storefront.controls.in_stock_only') }}</span>
            </label>

            <div class="cat-actions">
                <button type="submit" class="cat-btn">{{ __('site.storefront.controls.apply') }}</button>
                @if ($hasActiveFilters)
                    {{-- "Clear" → bare GET to /, drops every query param. --}}
                    <a class="cat-btn cat-btn-ghost" href="/">{{ __('site.storefront.controls.clear') }}</a>
                @endif
            </div>
        </div>
    </form>

    <div class="cat-result-line">
        @if ($totalCount === 0)
            <span class="cat-no-results">{{ __('site.storefront.controls.no_results') }}</span>
        @else
            <span>{{ trans_choice('site.storefront.controls.result_count', $totalCount, ['count' => $totalCount]) }}</span>
        @endif
        @if ($filters['q'])
            <span class="cat-query-pill">"{{ $filters['q'] }}"</span>
        @endif
    </div>
</div>

<style>
    /* Scoped + utility-ish. Themes can wrap or override .cat-* freely. */
    .cat-controls {
        margin: 0 0 2rem;
        padding: 1.25rem;
        background: rgba(0, 0, 0, .025);
        border: 1px solid rgba(0, 0, 0, .08);
        border-radius: 12px;
    }
    .cat-form { display: flex; flex-direction: column; gap: .875rem; }
    .cat-row {
        display: grid;
        gap: .75rem;
        align-items: end;
    }
    .cat-row-primary {
        grid-template-columns: 2fr 1fr 1fr;
    }
    .cat-row-secondary {
        grid-template-columns: 1fr 1fr 1fr auto;
    }
    .cat-field { display: flex; flex-direction: column; gap: .25rem; }
    .cat-label {
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(0, 0, 0, .55);
        font-weight: 600;
    }
    .cat-field input[type="search"],
    .cat-field input[type="number"],
    .cat-field select {
        padding: .55rem .75rem;
        background: white;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 8px;
        font: inherit;
        font-size: .9375rem;
        color: inherit;
        width: 100%;
        box-sizing: border-box;
    }
    .cat-field input:focus,
    .cat-field select:focus { outline: none; border-color: rgba(0, 0, 0, .5); }
    .cat-field-checkbox {
        flex-direction: row;
        align-items: center;
        gap: .5rem;
        font-size: .875rem;
        padding-bottom: .55rem; /* align with input baseline */
    }
    .cat-actions { display: flex; gap: .5rem; align-items: stretch; padding-bottom: 0; }
    .cat-btn {
        padding: .55rem 1.125rem;
        background: #111;
        color: white;
        border: 0;
        border-radius: 8px;
        font: inherit;
        font-weight: 600;
        font-size: .875rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: opacity .15s ease, transform .15s ease;
    }
    .cat-btn:hover { opacity: .9; transform: translateY(-1px); }
    .cat-btn-ghost {
        background: transparent;
        color: rgba(0, 0, 0, .65);
        border: 1px solid rgba(0, 0, 0, .15);
    }
    .cat-result-line {
        display: flex; gap: .5rem; align-items: center;
        margin-top: .875rem;
        font-size: .8125rem;
        color: rgba(0, 0, 0, .6);
    }
    .cat-query-pill {
        padding: .125rem .5rem;
        background: rgba(0, 0, 0, .07);
        border-radius: 999px;
        font-weight: 600;
        color: rgba(0, 0, 0, .75);
    }
    .cat-no-results { color: #b91c1c; font-weight: 600; }

    @media (max-width: 720px) {
        .cat-row-primary, .cat-row-secondary { grid-template-columns: 1fr; }
        .cat-actions { padding-bottom: 0; }
        .cat-btn { width: 100%; justify-content: center; }
    }
</style>
