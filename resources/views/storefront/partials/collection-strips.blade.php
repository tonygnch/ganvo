@php
    /*
     | Featured collections strip — one row per is_featured collection,
     | each showing the operator-curated product list with a "View all"
     | link to /collections/{slug}. Themes include this on their index
     | page, typically above the main product grid.
     |
     | Input:
     |   $featuredCollections  Collection<Collection> (each preloaded
     |                         with up to 8 products by the controller)
     |
     | Renders nothing when the merchant hasn't featured anything yet.
     | Scoped under .cs-* so themes can wrap or override the look.
     */
@endphp

@if ($featuredCollections->isNotEmpty())
    @foreach ($featuredCollections as $collection)
        @php $items = $collection->products; @endphp
        @if ($items->isNotEmpty())
            <section class="cs-strip">
                @if ($collection->banner_path)
                    <div class="cs-banner" style="background-image: url('{{ $collection->bannerUrl() }}');" aria-hidden="true"></div>
                @endif
                <header class="cs-head">
                    <div>
                        <h2>{{ $collection->title }}</h2>
                        @if ($collection->description)
                            <p>{{ $collection->description }}</p>
                        @endif
                    </div>
                    <a class="cs-view-all" href="/collections/{{ $collection->slug }}">{{ __('site.storefront.collections.view_all') }} →</a>
                </header>
                <div class="cs-grid">
                    @foreach ($items as $product)
                        <a href="/products/{{ $product->slug }}" class="cs-card">
                            <div class="cs-img">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
                                @endif
                            </div>
                            <div class="cs-meta">
                                <span class="cs-name">{{ $product->name }}</span>
                                <span class="cs-price">@money($product->price_cents)</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    <style>
        .cs-strip {
            position: relative;
            margin: 0 0 3rem;
            padding: 1.5rem;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 14px;
            background: rgba(0, 0, 0, .015);
            overflow: hidden;
        }
        .cs-banner {
            position: absolute; inset: 0;
            background-size: cover;
            background-position: center;
            opacity: .25;
            z-index: 0;
            pointer-events: none;
        }
        .cs-banner + .cs-head, .cs-banner ~ .cs-grid { position: relative; z-index: 1; }
        .cs-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin: 0 0 1.25rem;
            flex-wrap: wrap;
        }
        .cs-head h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .cs-head p {
            margin: .25rem 0 0;
            color: rgba(0, 0, 0, .6);
            font-size: .9375rem;
            max-width: 50ch;
        }
        .cs-view-all {
            font-size: .8125rem;
            font-weight: 600;
            color: inherit;
            text-decoration: none;
            padding: .5rem .875rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, .04);
            transition: background-color .15s ease;
        }
        .cs-view-all:hover { background: rgba(0, 0, 0, .1); }
        .cs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }
        .cs-card {
            display: flex;
            flex-direction: column;
            gap: .625rem;
            color: inherit;
            text-decoration: none;
            transition: transform .15s ease;
        }
        .cs-card:hover { transform: translateY(-2px); }
        .cs-img {
            aspect-ratio: 1 / 1;
            background: rgba(0, 0, 0, .06);
            border-radius: 10px;
            overflow: hidden;
        }
        .cs-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease; }
        .cs-card:hover .cs-img img { transform: scale(1.04); }
        .cs-meta {
            display: flex;
            flex-direction: column;
            gap: .125rem;
            padding: 0 .25rem;
        }
        .cs-name { font-size: .875rem; font-weight: 600; line-height: 1.3; }
        .cs-price { font-size: .8125rem; color: rgba(0, 0, 0, .6); font-variant-numeric: tabular-nums; }

        @media (max-width: 540px) {
            .cs-grid { grid-template-columns: repeat(2, 1fr); }
            .cs-head { flex-direction: column; align-items: flex-start; }
        }
    </style>
@endif
