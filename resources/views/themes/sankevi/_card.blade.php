@php
    /*
     | Sankevi product card — a plate in the catalogue: the photograph cut at
     | two corners, its sheet number and section set in the spec voice beneath,
     | the name in the serif and the price alone on a ruled foot.
     | Expects $product, optional $badge.
     |
     | No "виж" beside the price: the whole plate is the link, so the label
     | only repeated what tapping already does — and it was the thing crowding
     | a price range off its line on a phone.
     */
    $badge = $badge ?? null;
    // When the including page passes gvDelay, the card animates via the
    // storefront kit (GSAP stagger); otherwise the legacy .reveal observer.
    $gvDelay = $gvDelay ?? null;
    $imgUrl = $product->image_path
        ? \Illuminate\Support\Facades\Storage::url($product->image_path)
        : null;
    // Category label only when the relation is already loaded (no N+1).
    $catLabel = $product->relationLoaded('categories') && $product->categories->isNotEmpty()
        ? $product->categories->first()->name
        : null;
@endphp

<a class="pcard {{ $gvDelay === null ? 'reveal' : '' }}"
   @if ($gvDelay !== null) data-gv-reveal data-gv-delay="{{ $gvDelay }}" @endif
   href="/products/{{ $product->slug }}">
    <div class="pic cut {{ $imgUrl ? '' : 'ph' }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($imgUrl)
            <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="board-glyph" aria-hidden="true"><i></i></span>
        @endif
    </div>
    <div class="line">
        <span class="sheet" aria-hidden="true"></span>
        @if ($catLabel)<span class="cat">{{ $catLabel }}</span>@endif
    </div>
    <h3>{{ $product->name }}</h3>
    <div class="foot">
        @php
            $gvRange = $product->priceRange();
            $gvCur = $displayCurrency ?? (isset($store) ? $store->currency : 'EUR');
            $gvPrice = $gvRange
                ? __('site.storefront.product.price_range', [
                    'min' => \App\Services\Money::display($gvRange[0], $displayRate ?? 1.0, $gvCur),
                    'max' => \App\Services\Money::display($gvRange[1], $displayRate ?? 1.0, $gvCur),
                ])
                : \App\Services\Money::display((int) $product->price_cents, $displayRate ?? 1.0, $gvCur);
        @endphp
        <span class="pr">{{ $gvPrice }}@if ($u = $product->priceUnitSuffix())<i class="pu">{{ $u }}</i>@endif</span>
    </div>
</a>
