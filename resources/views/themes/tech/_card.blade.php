@php
    /* Volt product card. Inputs: $product, optional $badge (string|null). */
    $cat = optional($product->categories->first())->name ?? '';
@endphp
<a class="pcard rv" href="/products/{{ $product->slug }}">
    <div class="img">
        @if ($product->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
        @endif
        @if (! empty($badge))<div class="badge">{{ $badge }}</div>@endif
    </div>
    @if ($cat)<div class="cat">{{ $cat }}</div>@endif
    <div class="nm">{{ $product->name }}</div>
    <div class="foot">
        <span class="pr">@money($product->price_cents)</span>
        <span class="add" aria-hidden="true">+</span>
    </div>
</a>
