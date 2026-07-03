@php
    /*
     | Wick product card — jar label: a dark framed amber-jar slot (or the
     | real product photo) topped by a mono category tag, a serif name and a
     | display-weight price with a + add cue. Expects $product, optional $badge.
     */
    $badge = $badge ?? null;
    $imgUrl = $product->image_path
        ? \Illuminate\Support\Facades\Storage::url($product->image_path)
        : null;
    // Category label only when the relation is already loaded (no N+1).
    $catLabel = $product->relationLoaded('categories') && $product->categories->isNotEmpty()
        ? $product->categories->first()->name
        : null;
@endphp

<a class="bcard reveal" href="/products/{{ $product->slug }}">
    <div class="pic {{ $imgUrl ? '' : 'bloomph' }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($imgUrl)
            <img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="jar-mark" aria-hidden="true"></span>
        @endif
    </div>
    @if ($catLabel)<div class="cat">{{ $catLabel }}</div>@endif
    <h3>{{ $product->name }}</h3>
    <div class="foot"><span class="pr">@money($product->price_cents)</span><span class="add" aria-hidden="true">+</span></div>
</a>
