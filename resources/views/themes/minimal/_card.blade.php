@php $cat = optional($product->categories->first())->name ?? ''; @endphp
<a class="pcard rv" href="/products/{{ $product->slug }}">
    <div class="img">@if ($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">@endif</div>
    @if ($cat)<div class="cat">{{ $cat }}</div>@endif
    <div class="nm">{{ $product->name }}</div>
    <div class="pr">@money($product->price_cents)</div>
    <div class="add">{{ __('site.storefront.product.add_to_cart') }}</div>
</a>
