@php
    $title = $collection->title;
@endphp
@extends('themes.sankevi.layout')

@section('content')
    @php
        $bannerUrl = $collection->banner_path
            ? \Illuminate\Support\Facades\Storage::url($collection->banner_path)
            : null;
    @endphp

    <style>
        /*
         | Sankevi collection — one plate, blown up. The merchant's banner is
         | cut at two corners and the title steps off its bottom edge onto the
         | page, the same overlap the home hero uses.
         */
        .coll-hero { position: relative; margin-top: 34px; }
        .coll-hero .art { position: relative; aspect-ratio: 21 / 9; overflow: hidden; background: var(--surface2); }
        .coll-hero .art img { width: 100%; height: 100%; object-fit: cover; }
        .coll-hero .art::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(21, 18, 13, .2), rgba(21, 18, 13, .88)); }
        .coll-hero .overlay { position: relative; z-index: 2; margin: -78px 0 0 44px; max-width: 700px; }
        .coll-hero .overlay .kicker { display: block; margin-bottom: 14px; }
        .coll-hero .overlay h1 { font-family: var(--display); font-weight: 500; font-size: clamp(38px, 5.4vw, 72px); line-height: 1.02; letter-spacing: -.02em; }
        .coll-hero .overlay h1 em { font-style: italic; color: var(--accent); }
        .coll-hero .overlay p { color: var(--muted); max-width: 54ch; margin-top: 14px; font-size: 15.5px; }

        .toolbar { display: flex; align-items: center; gap: 16px; margin: 46px 0 26px; }
        .toolbar .count { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); font-variant-numeric: tabular-nums; }
        .toolbar .rule { flex: 1; height: 1px; background: var(--line); }
        .toolbar .crumb { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .toolbar .crumb a:hover { color: var(--accent); }

        .coll-empty { padding: 90px 24px; text-align: center; font-family: var(--display); font-style: italic; font-size: 24px; color: var(--muted); border: 1px solid var(--line); }

        @media (max-width: 760px) {
            .coll-hero .art { aspect-ratio: 4 / 3; }
            .coll-hero .overlay { margin: -54px 0 0 18px; }
            .coll-empty { padding: 56px 18px; font-size: 20px; }
        }
    </style>

    <main>
        <div class="wrap">
            @if ($bannerUrl)
                <section class="coll-hero reveal">
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true" style="top: 30px;"><b>{{ $theme->label('gutter_index') }}</b> {{ __('site.storefront.featured.eyebrow') }}</span>
                    @endif
                    <div class="art cut cut-lg"><img src="{{ $bannerUrl }}" alt="{{ $collection->title }}"></div>
                    <div class="overlay">
                        <span class="kicker">{{ __('site.storefront.featured.eyebrow') }}</span>
                        <h1>{{ $collection->title }}</h1>
                        @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                    </div>
                </section>
            @else
                <div class="page-head reveal">
                    @if ($theme->on('gutter_index'))
                        <span class="gx" aria-hidden="true" style="top: 60px;"><b>{{ $theme->label('gutter_index') }}</b> {{ __('site.storefront.featured.eyebrow') }}</span>
                    @endif
                    <div class="crumb"><a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a></div>
                    <h1>{{ $collection->title }}</h1>
                    @if ($collection->description)<p>{{ $collection->description }}</p>@endif
                </div>
            @endif

            <div class="toolbar reveal">
                <span class="count">{{ trans_choice('site.storefront.controls.result_count', $products->total(), ['count' => $products->total()]) }}</span>
                <span class="rule"></span>
                <span class="crumb">{{ __('site.storefront.shop_all.h2') }}</span>
            </div>

            @if ($products->isEmpty())
                <div class="coll-empty reveal">{{ __('site.storefront.no_products') }}</div>
            @else
                <div class="shelf {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}" style="--sheet-label: '{{ str_replace(['\\', '\''], '', $theme->label('sheet_marks')) }} '">
                    @foreach ($products as $product)
                        @include('themes.sankevi._card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>
                @include('storefront.partials.pagination')
            @endif
        </div>
    </main>
@endsection
