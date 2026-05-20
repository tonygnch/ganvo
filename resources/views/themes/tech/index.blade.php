@extends('themes.tech.layout')

@section('content')
    <style>
        .tech-section { padding: 4rem 0 5rem; }
        .tech-section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 2rem;
            margin: 0 0 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--hair);
        }
        .tech-section-head h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .tech-section-head .meta {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--text-soft);
        }
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .tech-card {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
            color: inherit;
        }
        .tech-card:hover {
            border-color: var(--hair-strong);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -10px rgba(15, 23, 42, .15);
        }
        .tech-image {
            position: relative;
            aspect-ratio: 4 / 3;
            background: var(--surface-2);
            overflow: hidden;
        }
        .tech-image img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .35s ease;
        }
        .tech-card:hover .tech-image img { transform: scale(1.05); }
        .tech-image .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-family: var(--mono);
            font-size: 0.6875rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .tech-image .stock-badge {
            position: absolute; top: .75rem; right: .75rem;
            background: var(--surface);
            border: 1px solid var(--hair);
            color: var(--text);
            font-family: var(--mono);
            font-size: 0.6875rem;
            font-weight: 600;
            padding: .25rem .5rem;
            border-radius: 4px;
        }
        .tech-image .stock-badge.low { background: var(--primary-soft); color: var(--primary-strong); border-color: color-mix(in srgb, var(--primary) 25%, transparent); }

        .tech-body {
            padding: 1.125rem 1.25rem 1.25rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .tech-body h3 {
            margin: 0 0 .375rem;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            line-height: 1.3;
        }
        .tech-body .price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 .875rem;
            font-variant-numeric: tabular-nums;
        }
        .tech-specs {
            margin: auto 0 0;
            padding-top: .875rem;
            border-top: 1px dashed var(--hair);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .375rem .75rem;
        }
        .tech-specs dt {
            font-family: var(--mono);
            font-size: 0.625rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin: 0;
        }
        .tech-specs dd {
            margin: 0;
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--text);
            font-weight: 600;
            text-align: right;
        }

        .tech-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 1rem;
            border: 1px dashed var(--hair);
            border-radius: 12px;
            color: var(--text-soft);
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

    <section class="container tech-section" id="shop">
        <div class="tech-section-head">
            <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            <span class="meta">{{ $products->count() }} {{ str()->plural('item', $products->count()) }}</span>
        </div>

        <div class="tech-grid">
            @forelse ($products as $product)
                <a href="/products/{{ $product->slug }}" class="tech-card">
                    <div class="tech-image">
                        @if ($product->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                        @else
                            <span class="placeholder">{{ __('site.storefront.product.no_image') }}</span>
                        @endif
                        @if ($product->stock_quantity > 0)
                            <span class="stock-badge {{ $product->stock_quantity < 10 ? 'low' : '' }}">
                                @if ($product->stock_quantity < 10)
                                    {{ __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) }}
                                @else
                                    {{ __('site.storefront.product.in_stock_full') }}
                                @endif
                            </span>
                        @endif
                    </div>
                    <div class="tech-body">
                        <h3>{{ $product->name }}</h3>
                        <div class="price">@money($product->price_cents)</div>
                        <dl class="tech-specs">
                            <dt>SKU</dt>
                            <dd>#{{ str_pad((string) $product->id, 4, '0', STR_PAD_LEFT) }}</dd>
                            <dt>Stock</dt>
                            <dd>{{ $product->stock_quantity }}</dd>
                        </dl>
                    </div>
                </a>
            @empty
                <div class="tech-empty">{{ __('site.storefront.no_products') }}</div>
            @endforelse
        </div>
    </section>
@endsection
