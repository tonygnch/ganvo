@extends('themes.minimal.layout')

@section('content')
    <style>
        .list { list-style: none; padding: 0; margin: 0; }
        .list li {
            padding: 1.5rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .list li:last-child { border-bottom: 0; }
        .list a {
            color: var(--secondary);
            font-size: 1.125rem;
            flex: 1;
        }
        .list .price {
            color: #666;
            font-size: 0.875rem;
            margin-left: 1rem;
            white-space: nowrap;
        }
        .empty {
            text-align: center;
            padding: 4rem 0;
            color: #999;
            font-style: italic;
        }
    </style>

    @if ($products->isEmpty())
        <p class="empty">{{ __('site.storefront.no_products') }}</p>
    @else
        <ul class="list">
            @foreach ($products as $product)
                <li>
                    <a href="/products/{{ $product->slug }}">{{ $product->name }}</a>
                    <span class="price">{{ number_format($product->price_cents / 100, 2) }} {{ $product->currency }}</span>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
