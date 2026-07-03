@php
    /*
     | Forma product card — clean accessory tile: rounded image block, Sora name,
     | tabular price and a little "+" affordance. No photos in the demo, so the
     | image block falls back to a hatched / cobalt-wash placeholder.
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
    <div class="pic {{ $imgUrl ? '' : ($product->id % 2 ? 'ph' : 'bloomph') }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($imgUrl)<img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">@endif
    </div>
    @if ($catLabel)<div class="cat">{{ $catLabel }}</div>@endif
    <h3>{{ $product->name }}</h3>
    <div class="foot" style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
        <span class="pr">@money($product->price_cents)</span>
        <span class="add" aria-hidden="true" style="width:32px;height:32px;border:1px solid var(--line2);border-radius:8px;font-size:17px;display:grid;place-items:center;color:var(--muted)">+</span>
    </div>
</a>
