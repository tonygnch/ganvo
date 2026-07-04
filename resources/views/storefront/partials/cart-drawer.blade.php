{{--
 | Slide-out cart drawer — shared across themes, skinned by each theme's
 | token contract (see resources/css/storefront.css). Requires the storefront
 | kit bundle (@vite storefront.js) which registers the Alpine $store.gvCart
 | and intercepts form[data-gv-add] submissions.
 |
 | Include once in a theme layout, near </body>:
 |   @include('storefront.partials.cart-drawer')
--}}
<div x-data x-cloak>
    {{-- veil --}}
    <div class="gv-drawer-veil"
         x-show="$store.gvCart.open"
         x-transition:enter="gv-fade-enter" x-transition:enter-start="gv-fade-off"
         x-transition:leave="gv-fade-leave" x-transition:leave-end="gv-fade-off"
         @click="$store.gvCart.closeDrawer()"></div>

    {{-- drawer --}}
    <aside class="gv-drawer"
           x-show="$store.gvCart.open"
           x-transition:enter="gv-slide-enter" x-transition:enter-start="gv-slide-off"
           x-transition:leave="gv-slide-leave" x-transition:leave-end="gv-slide-off"
           @keydown.escape.window="$store.gvCart.closeDrawer()"
           role="dialog" aria-modal="true" aria-label="{{ __('site.cart.title') }}">

        <div class="gv-head">
            <h2>{{ __('site.cart.title') }} <span x-show="$store.gvCart.count" x-text="'(' + $store.gvCart.count + ')'"></span></h2>
            <button type="button" class="gv-close" @click="$store.gvCart.closeDrawer()" aria-label="{{ __('site.storefront.product.close') }}">✕</button>
        </div>

        <div class="gv-flash" x-show="$store.gvCart.flash" x-text="$store.gvCart.flash"></div>

        <div class="gv-lines">
            <template x-if="!$store.gvCart.lines.length">
                <div class="gv-empty">{{ __('site.cart.empty_title') }}</div>
            </template>

            <template x-for="line in $store.gvCart.lines" :key="line.line_id">
                <div class="gv-line">
                    <a :href="line.url" class="gv-img">
                        <template x-if="line.image"><img :src="line.image" :alt="line.name" loading="lazy"></template>
                    </a>
                    <div>
                        <a :href="line.url" class="gv-name" x-text="line.name"></a>
                        <div class="gv-meta" x-show="line.variant" x-text="line.variant"></div>
                        <div class="gv-qty">
                            <button type="button"
                                    :disabled="$store.gvCart.busy"
                                    @click="$store.gvCart.mutate('/cart/' + line.line_id, { _token: '{{ csrf_token() }}', _method: 'PATCH', quantity: line.quantity - 1 })"
                                    aria-label="{{ __('site.cart.decrease') }}">−</button>
                            <span class="gv-n" x-text="line.quantity"></span>
                            <button type="button"
                                    :disabled="$store.gvCart.busy"
                                    @click="$store.gvCart.mutate('/cart/' + line.line_id, { _token: '{{ csrf_token() }}', _method: 'PATCH', quantity: line.quantity + 1 })"
                                    aria-label="{{ __('site.cart.increase') }}">+</button>
                        </div>
                    </div>
                    <div class="gv-price" x-text="line.subtotal"></div>
                </div>
            </template>
        </div>

        <div class="gv-foot" x-show="$store.gvCart.lines.length">
            <div class="gv-total">
                <span>{{ __('site.cart.subtotal') }}</span>
                <span class="gv-amount" x-text="$store.gvCart.subtotal"></span>
            </div>
            <a href="/checkout" class="gv-cta">{{ __('site.cart.checkout') }}</a>
            <button type="button" class="gv-continue" @click="$store.gvCart.closeDrawer()">{{ __('site.cart.continue_shopping') }}</button>
        </div>
    </aside>
</div>
