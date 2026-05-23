@php
    $title = $collection->title;
@endphp
@extends('themes.' . (\App\Themes\ThemeRegistry::exists($store->theme) ? $store->theme : 'default') . '.layout')

@section('content')
    {{-- Generic collection browse page. Themes can override by shipping
         themes/{theme}/collection.blade.php — until then, this renders
         inside the chosen theme's layout for consistent chrome. --}}
    <style>
        .col-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
        .col-banner {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            margin: 0 0 2rem;
            background: rgba(0, 0, 0, .04);
        }
        .col-banner img { width: 100%; height: auto; display: block; }
        .col-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,.05) 0%, rgba(0,0,0,.45) 100%);
            display: flex;
            align-items: flex-end;
            padding: 2rem;
            color: white;
        }
        .col-banner-overlay h1 { margin: 0; font-size: clamp(1.75rem, 4vw, 2.75rem); }

        .col-head { margin: 0 0 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,.08); }
        .col-crumbs { font-size: .8125rem; color: #6b7280; margin-bottom: .5rem; }
        .col-crumbs a { color: inherit; text-decoration: none; }
        .col-crumbs a:hover { color: #111; }
        .col-head h1 { margin: 0; font-size: 2rem; letter-spacing: -0.01em; }
        .col-head .desc { margin: .5rem 0 0; color: #4b5563; max-width: 720px; }

        .col-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .col-card {
            display: block; color: inherit; text-decoration: none;
            border: 1px solid rgba(0,0,0,.08); border-radius: 12px;
            overflow: hidden; transition: transform .15s, box-shadow .15s;
        }
        .col-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -16px rgba(0,0,0,.2); }
        .col-card .img { aspect-ratio: 4/3; background: #f3f4f6; overflow: hidden; }
        .col-card .img img { width: 100%; height: 100%; object-fit: cover; }
        .col-card .body { padding: .875rem 1rem; }
        .col-card .name { margin: 0 0 .25rem; font-weight: 600; font-size: .9375rem; }
        .col-card .price { margin: 0; font-size: .875rem; color: #4b5563; }
        .col-empty {
            padding: 4rem 1rem; text-align: center; color: #6b7280;
            border: 1px dashed rgba(0,0,0,.15); border-radius: 12px;
        }
    </style>

    <div class="col-page">
        @if ($collection->banner_path)
            <div class="col-banner">
                <img src="{{ $collection->bannerUrl() }}" alt="">
                <div class="col-banner-overlay">
                    <h1>{{ $collection->title }}</h1>
                </div>
            </div>
        @endif

        <div class="col-head">
            <nav class="col-crumbs" aria-label="Breadcrumb">
                <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
                &nbsp;/&nbsp;<span>{{ $collection->title }}</span>
            </nav>
            @unless ($collection->banner_path)
                <h1>{{ $collection->title }}</h1>
            @endunless
            @if ($collection->description)
                <p class="desc">{{ $collection->description }}</p>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="col-empty">{{ __('site.storefront.collections.empty') }}</div>
        @else
            <div class="col-grid">
                @foreach ($products as $product)
                    <a class="col-card" href="/products/{{ $product->slug }}">
                        <div class="img">
                            @if ($product->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
                            @endif
                        </div>
                        <div class="body">
                            <p class="name">{{ $product->name }}</p>
                            <p class="price">@money($product->price_cents)</p>
                        </div>
                    </a>
                @endforeach
            </div>

            @include('storefront.partials.pagination')
        @endif
    </div>
@endsection
