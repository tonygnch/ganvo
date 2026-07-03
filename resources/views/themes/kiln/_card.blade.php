@php
    /*
     | Kiln product card — quiet gallery piece: a soft stone-gradient image
     | block that lifts on hover, with a small uppercase category label,
     | a serif name and a tabular price. Expects $product and optional $badge.
     */
    $badge = $badge ?? null;
    $imgUrl = $product->image_path
        ? \Illuminate\Support\Facades\Storage::url($product->image_path)
        : null;
    // Category label only when the relation is already loaded (no N+1).
    $catLabel = $product->relationLoaded('categories') && $product->categories->isNotEmpty()
        ? $product->categories->first()->name
        : null;
    // Numbered works — catalogue index inherited from the enclosing loop.
    $workNum = isset($loop) ? str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) : null;
@endphp

<a class="bcard reveal" href="/products/{{ $product->slug }}">
    <div class="pic {{ $imgUrl ? '' : ($product->id % 2 ? 'ph' : 'bloomph') }}">
        @if ($badge)<div class="badge">{{ $badge }}</div>@endif
        @if ($workNum)<span class="idx" aria-hidden="true">{{ $workNum }}</span>@endif
        @if ($imgUrl)<img src="{{ $imgUrl }}" alt="{{ $product->name }}" loading="lazy">@endif
    </div>
    @if ($catLabel)<div class="cat">{{ $catLabel }}</div>@endif
    <h3>{{ $product->name }}</h3>
    <div class="pr">@money($product->price_cents)</div>
</a>
