@php
    $title = $product->name;
@endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .back {
            display: inline-block;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        article h2 {
            font-weight: 300;
            font-size: 2rem;
            margin: 0 0 1rem;
        }
        .price {
            color: var(--primary);
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }
        .desc { color: #555; margin-bottom: 2.5rem; }
        button {
            background: transparent;
            color: var(--secondary);
            border: 1px solid var(--secondary);
            padding: .75rem 2rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
        }
        button:hover:not([disabled]) { background: var(--secondary); color: white; }
        button[disabled] { opacity: 0.5; cursor: not-allowed; }
    </style>

    <style>
        .product-image {
            margin: 0 0 2rem;
            max-height: 320px;
            display: flex;
            justify-content: center;
        }
        .product-image img { max-height: 320px; max-width: 100%; }
    </style>

    <a href="/" class="back">← {{ __('site.storefront.footer.all_products') }}</a>
    @if ($product->image_path)
        <div class="product-image">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
        </div>
    @endif
    <article>
        <h2>{{ $product->name }}</h2>
        <div class="price">{{ number_format($product->price_cents / 100, 2) }} {{ $product->currency }}</div>
        <p class="desc">{{ $product->description }}</p>
        <form method="post" action="/cart/add/{{ $product->slug }}">
            @csrf
            <button type="submit">{{ __('site.storefront.product.add_to_cart') }}</button>
        </form>
    </article>
@endsection
