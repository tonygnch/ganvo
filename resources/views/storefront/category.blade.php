@php
    $title = $category->name;
@endphp
@extends('themes.' . (\App\Themes\ThemeRegistry::exists($store->theme) ? $store->theme : 'default') . '.layout')

@section('content')
    {{-- Generic category browse page. Themes can override by shipping
         themes/{theme}/category.blade.php; until they do, this view
         renders inside whichever theme layout the store has chosen,
         keeping the chrome consistent with the rest of the storefront. --}}
    <style>
        .cat-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
        .cat-head { display: flex; flex-direction: column; gap: .75rem; margin: 0 0 2rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,.08); }
        .cat-crumbs { font-size: .8125rem; color: #6b7280; }
        .cat-crumbs a { color: inherit; text-decoration: none; }
        .cat-crumbs a:hover { color: #111; }
        .cat-head h1 { margin: 0; font-size: 2rem; letter-spacing: -0.01em; }
        .cat-head .desc { margin: 0; color: #4b5563; max-width: 720px; }

        .cat-children { display: flex; flex-wrap: wrap; gap: .5rem; margin: 0 0 2rem; }
        .cat-children a {
            padding: .375rem .875rem; border: 1px solid rgba(0,0,0,.12);
            border-radius: 999px; font-size: .8125rem; color: #111;
            text-decoration: none; transition: background-color .15s, border-color .15s;
        }
        .cat-children a:hover { background: #f3f4f6; border-color: rgba(0,0,0,.25); }

        .cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .cat-card {
            display: block; color: inherit; text-decoration: none;
            border: 1px solid rgba(0,0,0,.08); border-radius: 12px;
            overflow: hidden; transition: transform .15s, box-shadow .15s;
        }
        .cat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -16px rgba(0,0,0,.2); }
        .cat-card .img { aspect-ratio: 4/3; background: #f3f4f6; overflow: hidden; }
        .cat-card .img img { width: 100%; height: 100%; object-fit: cover; }
        .cat-card .body { padding: .875rem 1rem; }
        .cat-card .name { margin: 0 0 .25rem; font-weight: 600; font-size: .9375rem; }
        .cat-card .price { margin: 0; font-size: .875rem; color: #4b5563; }
        .cat-empty {
            padding: 4rem 1rem; text-align: center; color: #6b7280;
            border: 1px dashed rgba(0,0,0,.15); border-radius: 12px;
        }
    </style>

    <div class="cat-page">
        <div class="cat-head">
            <nav class="cat-crumbs" aria-label="Breadcrumb">
                <a href="/">{{ __('site.cart.continue_shopping_short') ?: 'Home' }}</a>
                @if ($category->parent)
                    &nbsp;/&nbsp;<a href="/categories/{{ $category->parent->slug }}">{{ $category->parent->name }}</a>
                @endif
                &nbsp;/&nbsp;<span>{{ $category->name }}</span>
            </nav>
            <h1>{{ $category->name }}</h1>
            @if ($category->description)
                <p class="desc">{{ $category->description }}</p>
            @endif
        </div>

        @php
            $children = $category->children()->where('is_active', true)->get();
        @endphp
        @if ($children->isNotEmpty())
            <div class="cat-children">
                @foreach ($children as $child)
                    <a href="/categories/{{ $child->slug }}">{{ $child->name }}</a>
                @endforeach
            </div>
        @endif

        @if ($products->isEmpty())
            <div class="cat-empty">No products in this category yet.</div>
        @else
            <div class="cat-grid">
                @foreach ($products as $product)
                    <a class="cat-card" href="/products/{{ $product->slug }}">
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
