@extends('themes.menu.layout')

@section('content')
    <style>
        /* The "menu sheet" — single-column list of products styled like a
           printed cafe menu. Each row: name + description on the left,
           dotted leader line bridging to the price on the right. */
        .menu-sheet {
            max-width: 760px;
            margin: 0 auto;
            padding: 4rem 1.5rem 5rem;
        }
        .menu-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-align: center;
            margin: 0 0 .75rem;
        }
        .menu-heading {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-style: italic;
            font-size: clamp(2rem, 4vw, 2.75rem);
            text-align: center;
            margin: 0 0 .5rem;
            color: var(--ink);
        }
        .menu-ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            color: var(--ink-soft);
            margin: 0 0 3rem;
        }
        .menu-ornament::before,
        .menu-ornament::after {
            content: "";
            height: 1px;
            background: var(--rule);
            width: 70px;
        }
        .menu-ornament .dot { font-size: 0.5rem; }

        .menu-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 0;
            align-items: baseline;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--rule);
            color: var(--ink);
        }
        .menu-row:hover .menu-name { color: var(--primary-strong); }
        .menu-row-text {
            grid-column: 1;
            min-width: 0;
        }
        .menu-name {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            font-size: 1.375rem;
            letter-spacing: -0.005em;
            margin: 0 0 .25rem;
            transition: color .2s ease;
        }
        .menu-desc {
            color: var(--ink-soft);
            font-size: 0.9375rem;
            font-style: italic;
            margin: 0;
            max-width: 38rem;
            line-height: 1.55;
        }
        .menu-leader {
            grid-column: 2;
            border-bottom: 2px dotted var(--rule);
            margin: 0 .875rem .375rem;
            min-height: 1px;
            align-self: end;
        }
        .menu-price {
            grid-column: 3;
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            font-size: 1.375rem;
            color: var(--ink);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            align-self: start;
        }

        .menu-out {
            font-size: 0.6875rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin-left: .5rem;
        }

        .menu-empty {
            text-align: center;
            padding: 4rem 0;
            color: var(--ink-soft);
            font-style: italic;
            font-size: 1.125rem;
        }

        @media (max-width: 540px) {
            .menu-row { grid-template-columns: 1fr auto; }
            .menu-row-text { grid-column: 1; }
            .menu-leader { display: none; }
            .menu-price { grid-column: 2; }
        }
    </style>

    @php $csHero = $store->heroBanner(); @endphp

    @if ($csHero['enabled'] && ($csHero['title'] !== '' || $csHero['subtitle'] !== '' || $csHero['image_path']))
        <section class="custom-hero {{ $csHero['image_path'] ? 'with-image' : '' }}">
            @if ($csHero['image_path'])
                <div class="bg-img" style="background-image: url('{{ \Illuminate\Support\Facades\Storage::url($csHero['image_path']) }}');" aria-hidden="true"></div>
            @endif
            <div class="custom-hero-inner">
                @if ($csHero['title'] !== '')<h2>{{ $csHero['title'] }}</h2>@endif
                @if ($csHero['subtitle'] !== '')<p>{{ $csHero['subtitle'] }}</p>@endif
                @if ($csHero['cta_label'] !== '' && $csHero['cta_url'] !== '')
                    <a href="{{ $csHero['cta_url'] }}" class="cta">{{ $csHero['cta_label'] }}</a>
                @endif
            </div>
        </section>
    @endif

    <section class="menu-sheet" id="menu">
        <p class="menu-eyebrow">{{ __('site.storefront.shop_all.eyebrow') }}</p>
        <h2 class="menu-heading">{{ __('site.storefront.shop_all.h2') }}</h2>
        <div class="menu-ornament" aria-hidden="true"><span class="dot">●</span></div>

        @if ($products->isEmpty())
            <p class="menu-empty">{{ __('site.storefront.no_products') }}</p>
        @else
            @foreach ($products as $product)
                <a href="/products/{{ $product->slug }}" class="menu-row">
                    <div class="menu-row-text">
                        <h3 class="menu-name">
                            {{ $product->name }}
                            @if ($product->stock_quantity <= 0)
                                <span class="menu-out">{{ __('site.storefront.product.out_of_stock') ?? 'Out' }}</span>
                            @endif
                        </h3>
                        @if ($product->description)
                            <p class="menu-desc">{{ $product->description }}</p>
                        @endif
                    </div>
                    <span class="menu-leader" aria-hidden="true"></span>
                    <span class="menu-price">@money($product->price_cents)</span>
                </a>
            @endforeach
        @endif
    </section>
@endsection
