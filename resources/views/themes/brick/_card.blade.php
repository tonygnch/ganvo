@php
    /*
     | Brick product card — hard-bordered block with offset shadow, accent
     | price chip, optional corner tag. Expects $product and optional $badge.
     */
    $badge = $badge ?? null;
    $imgUrl = $product->image_path
        ? \Illuminate\Support\Facades\Storage::url($product->image_path)
        : null;
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
        @if ($badge)
            <span class="tag">{{ $badge }}</span>
        @endif
    </div>
    <div class="body">
        <div class="nm">{{ $product->name }}</div>
        <div class="pr">@money($product->price_cents)</div>
    </div>
</a>
