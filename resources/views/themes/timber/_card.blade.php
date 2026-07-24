@php
    /*
     | Timber product card — price-list entry: a light framed board slot (or
     | the real product photo) topped by a mono category tag, condensed-caps
     | name, spec chips and a bold price with a + add cue.
     | Expects $product, optional $badge.
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

<a class="bcard {{ $gvDelay === null ? 'reveal' : '' }}"
   @if ($gvDelay !== null) data-gv-reveal data-gv-delay="{{ $gvDelay }}" @endif
   href="/products/{{ $product->slug }}">
    <div class="pic {{ $imgUrl ? '' : 'ph' }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($imgUrl)
            <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="plank-mark" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
        @endif
    </div>
    @if ($catLabel)<div class="cat">{{ $catLabel }}</div>@endif
    <h3>{{ $product->name }}</h3>
    <div class="foot"><span class="pr">@money($product->price_cents)</span><span class="add" aria-hidden="true">+</span></div>
</a>
