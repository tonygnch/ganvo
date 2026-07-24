@php
    $title = $collection->title;
@endphp
@extends('themes.timber.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        /*
         | Timber collection — the signboard hung over one rack of the yard.
         | The merchant's banner becomes a walnut-shaded board with stencil caps
         | and a measuring edge along its foot; below it the tally rule, then the
         | price-list cards, lot-stamped like everything else on the floor.
         */
        .coll-hero { position: relative; border-radius: 10px; overflow: hidden; margin-top: 32px; min-height: 300px; display: flex; align-items: flex-end; background: var(--deep); border: 1px solid var(--line2); box-shadow: 0 2px 0 0 var(--line2); }
        .coll-hero img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .72; }
        .coll-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(36, 28, 18, .9), rgba(36, 28, 18, .3) 60%, transparent); z-index: 1; }
        .coll-hero .overlay { position: relative; z-index: 2; padding: 44px 40px; color: #f0e7d6; max-width: 640px; }
        .coll-hero .overlay .kicker { color: var(--accent); display: block; margin-bottom: 12px; }
        .coll-hero .overlay .kicker::before { content: "— "; color: rgba(240, 231, 214, .5); }
        .coll-hero .overlay h1 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(40px, 5vw, 62px); line-height: 1; }
        .coll-hero .overlay h1 em { font-style: normal; color: var(--accent); }
        .coll-hero .overlay p { font-size: 16px; max-width: 52ch; margin-top: 12px; color: rgba(240, 231, 214, .85); }
        /* measuring edge along the foot of the board */
        .coll-hero .rule-ticks { position: absolute; left: 0; right: 0; bottom: 0; z-index: 3; opacity: .55; }

        /* count toolbar — the tally rule above the rack */
        .toolbar { display: flex; align-items: center; gap: 14px; margin: 32px 0 8px; }
        .toolbar .count { font-family: var(--mono); font-size: 13px; font-weight: 600; letter-spacing: .08em; color: var(--accent-deep); font-variant-numeric: tabular-nums; }
        .toolbar .rule { flex: 1; height: 1px; background: var(--line); }
        .toolbar .crumb { font-family: var(--mono); font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .toolbar .crumb a:hover { color: var(--accent-deep); }

        .coll-empty { text-align: center; padding: 70px 24px; font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: 24px; color: var(--muted); }

        @media (max-width: 680px) {
            .coll-hero { min-height: 240px; margin-top: 22px; }
            .coll-hero .overlay { padding: 28px 22px; }
            .toolbar { margin: 26px 0 4px; }
            .coll-empty { padding: 48px 18px; font-size: 20px; }
        }
    </style>

    <main>
        <div class="wrap">
            @if ($bannerUrl)
                <section class="coll-hero reveal">
                    <img src="{{ $bannerUrl }}" alt="{{ $collection->title }}">
                    <div class="overlay">
                        <span class="kicker">{{ __('site.storefront.featured.eyebrow') }}</span>
                        <h1>{{ $collection->title }}</h1>
                        @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                    </div>
                    @if ($theme->on('ruler'))<div class="rule-ticks" aria-hidden="true"></div>@endif
                </section>
            @else
                <div class="page-head reveal">
                    <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a></div>
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                </div>
            @endif

            <div class="toolbar reveal">
                <span class="count">▮ {{ $products->total() }}</span>
                <span class="rule"></span>
                <span class="crumb">{{ __('site.storefront.shop_all.h2') }}</span>
            </div>

            @if ($products->isEmpty())
                <div class="coll-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="racks {{ $theme->on('lot_stamps') ? '' : 'no-lot' }}" style="--lot-label: '{{ str_replace(['\\', '\''], '', $theme->label('lot_stamps')) }} '">
                    @foreach ($products as $product)
                        @include('themes.timber._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
