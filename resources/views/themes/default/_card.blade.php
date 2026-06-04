@php
    /*
     | Atelier product card. Expects $product (App\Models\Product) and an
     | optional $badge string (e.g. "New", "Featured"). Renders the
     | hover-reveal "Quick view" overlay + serif name + price, matching
     | the Atelier - Fashion template's .pcard.
     */
    $badge = $badge ?? null;
    $imgUrl = $product->image_path
        ? \Illuminate\Support\Facades\Storage::url($product->image_path)
        : null;
    // Primary category name for the small kicker tag above the product
    // name. Cheap lookup via the already-loaded relation when present.
    $tagText = $badge
        ?? optional($product->categories->first())->name
        ?? __('site.storefront.featured.badge');
@endphp

<a class="pcard rv" href="/products/{{ $product->slug }}">
    <div class="imgwrap">
        <div class="img ph">
            @if ($imgUrl)
                <img src="{{ $imgUrl }}" alt="{{ $product->name }}">
            @else
                <span>{{ $product->name }}</span>
            @endif
        </div>
        <div class="over"><span class="q">{{ __('site.storefront.product.quick_view') }} +</span></div>
    </div>
    <div class="tag">{{ $tagText }}</div>
    <div class="rowt">
        <div class="nm">{{ $product->name }}</div>
        <div class="pr">@money($product->price_cents)</div>
    </div>
</a>
