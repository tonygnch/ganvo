@php
    /*
     | Ember product card — bordered "blend" tile: hard ink outline, warm cream
     | body, mono category label, Spectral name + accent price on a hairline
     | meta row. Hover lifts the tile with an offset block shadow.
     | Expects $product and optional $badge.
     */
    $badge = $badge ?? null;
    $imgUrl = $product->image_path
        ? \Illuminate\Support\Facades\Storage::url($product->image_path)
        : null;
    // Category label only when the relation is already loaded (no N+1).
    $catLabel = $product->relationLoaded('categories') && $product->categories->isNotEmpty()
        ? $product->categories->first()->name
        : null;
    // Roast-level pips — deterministic per product (2–4 of 5 lit), matching
    // the menu-board rows so the signature carries through the catalog.
    $roastOn = ($product->id % 3) + 2;
@endphp

<a class="bcard reveal" href="/products/{{ $product->slug }}">
    <div class="pic {{ $imgUrl ? '' : ($product->id % 2 ? 'ph' : 'bloomph') }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($imgUrl)<img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">@endif
    </div>
    <div class="body">
        @if ($catLabel)<div class="cat">{{ $catLabel }}</div>@endif
        <h3>{{ $product->name }}</h3>
        <div class="meta">
            <div class="roast" role="img" aria-label="{{ __('site.storefront.ember.roast_label') }} {{ $roastOn }}/5">
                @for ($i = 0; $i < 5; $i++)<i class="{{ $i < $roastOn ? 'on' : '' }}"></i>@endfor
            </div>
            <div class="pr">@money($product->price_cents)</div>
        </div>
    </div>
</a>
