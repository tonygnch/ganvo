@php
    /*
     | Posy product card — polaroid: soft cream card with a washi-tape strip,
     | slight rotation (straightens on hover), centred name + serif price.
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
@endphp

<a class="bcard reveal" href="/products/{{ $product->slug }}">
    <div class="tape"></div>
    <div class="pic {{ $imgUrl ? '' : ($product->id % 2 ? 'ph' : 'bloomph') }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($imgUrl)<img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">@endif
    </div>
    @if ($catLabel)<div class="cat">{{ $catLabel }}</div>@endif
    <h3>{{ $product->name }}</h3>
    <div class="pr">@money($product->price_cents)</div>
</a>
