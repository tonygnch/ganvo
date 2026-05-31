@php $cat = optional($product->categories->first())->name ?? ''; $badge = $badge ?? null; @endphp
<a class="pcard rv" href="/products/{{ $product->slug }}">
    <div class="img">@if ($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">@endif@if ($badge)<span class="badge">{{ $badge }}</span>@endif</div>
    @if ($cat)<div class="cat">{{ $cat }}</div>@endif
    <div class="nm">{{ $product->name }}</div>
    <div class="pr">@money($product->price_cents)</div>
</a>
