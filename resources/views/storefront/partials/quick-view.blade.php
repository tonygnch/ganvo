{{--
 | Quick-view modal — shared shell, themed via the token contract. Cards opt
 | in with a trigger that fills the Alpine store, e.g.:
 |
 |   <button type="button" @click="$store.gvQv.show({
 |       name: {{ Js::from($product->name) }},
 |       price: '@money($product->price_cents)',
 |       image: {{ Js::from($imgUrl) }},
 |       url: '/products/{{ $product->slug }}',
 |       desc: {{ Js::from(Str::limit(strip_tags($product->description ?? ''), 160)) }},
 |       action: {{ $product->hasVariants() ? 'null' : Js::from('/cart/add/' . $product->slug) }},
 |   })">Quick view</button>
 |
 | Variant products get a "view details" link instead of a direct add.
 | Include once in a theme layout, near </body>.
--}}
<div x-data x-cloak>
    <div class="gv-qv"
         x-show="$store.gvQv.open"
         x-transition:enter="gv-fade-enter" x-transition:enter-start="gv-fade-off"
         x-transition:leave="gv-fade-leave" x-transition:leave-end="gv-fade-off"
         @keydown.escape.window="$store.gvQv.close()"
         role="dialog" aria-modal="true">
        <div class="gv-drawer-veil" style="z-index: -1;" @click="$store.gvQv.close()"></div>
        <div class="gv-panel">
            <button type="button" class="gv-close" style="position: absolute; top: 12px; right: 14px;"
                    @click="$store.gvQv.close()" aria-label="{{ __('site.storefront.product.close') }}">✕</button>

            <div class="gv-img" style="aspect-ratio: 1 / 1; width: 100%; height: auto; border-radius: 8px; overflow: hidden; background: var(--primary-soft, var(--border, #eee));">
                <template x-if="$store.gvQv.item.image">
                    <img :src="$store.gvQv.item.image" :alt="$store.gvQv.item.name" style="width: 100%; height: 100%; object-fit: cover;">
                </template>
            </div>

            <div>
                <h3 style="font-size: 22px; margin: 0 0 6px;" x-text="$store.gvQv.item.name"></h3>
                <div style="font-weight: 700; color: var(--primary, inherit); margin-bottom: 12px;" x-text="$store.gvQv.item.price"></div>
                <p style="font-size: 14px; color: var(--text-muted, #666); margin-bottom: 18px;" x-text="$store.gvQv.item.desc"></p>

                <template x-if="$store.gvQv.item.action">
                    <form :action="$store.gvQv.item.action" method="post" data-gv-add
                          @submit.prevent="$store.gvCart.add($el); $store.gvQv.close();">
                        @csrf
                        <button type="submit" class="gv-cta">{{ __('site.storefront.product.add_to_cart') }}</button>
                    </form>
                </template>

                <a :href="$store.gvQv.item.url" class="gv-continue" style="text-align: left; padding: 10px 0;">
                    {{ __('site.storefront.featured.browse_all') }}
                </a>
            </div>
        </div>
    </div>
</div>
