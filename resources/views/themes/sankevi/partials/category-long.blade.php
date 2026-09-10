{{--
 | The category's full description — a button, and the text over the page.
 |
 | It used to be printed inline on a desktop and behind a button on a phone.
 | Two presentations of one paragraph, and the desktop one pushed the goods
 | down the page for anyone who wrote more than a line. One behaviour now, at
 | every width: the description is there for whoever wants it and costs the
 | catalogue nothing for whoever does not.
 |
 | It is a real <dialog>, opened with showModal(), so the focus trap, the
 | Escape key, the inert page behind and the backdrop come from the browser
 | rather than being reimplemented badly.
 |
 | The heading and the ✕ sit OUTSIDE the scrolling text, in a flex column, so
 | they stay put however far down the prose you are — which is what a long
 | description needs and what a sticky rule would only approximate.
 |
 | Expects $catLong (the text) and $catName.
--}}
@php($gvLong = trim((string) ($catLong ?? '')))
@if ($gvLong !== '')
    @php($gvLongId = 'catmore-h-' . \Illuminate\Support\Str::random(6))
    <div class="catmore">
        <button type="button" class="catmore-btn" data-catmore>
            {{ __('site.storefront.controls.category_more') }}
        </button>

        <dialog class="catmore-panel" data-catmore-panel aria-labelledby="{{ $gvLongId }}">
            <div class="catmore-head">
                <h2 class="catmore-h" id="{{ $gvLongId }}">{{ $catName ?? __('site.storefront.controls.category') }}</h2>
                <button type="button" class="catmore-close" data-catmore-close
                        aria-label="{{ __('site.storefront.product.close') }}">✕</button>
            </div>
            {{-- tabindex + autofocus on the SCROLLING region, not the ✕.
                 showModal() focuses the first control it finds, which put the
                 theme's focus ring around the close button for someone who had
                 just clicked with a mouse. Starting here instead means the
                 arrow keys scroll the prose the moment it opens, and
                 :focus-visible still draws the ring for whoever arrived by
                 keyboard. --}}
            {{-- data-lenis-prevent: the storefront kit's smooth scroll owns the
                 wheel for the whole document, so turning it over this panel
                 scrolled the PAGE behind the modal and left the prose exactly
                 where it was. The attribute is how the kit is told to leave a
                 scroller alone; the same one is on the configurator canvas and
                 the species wheel on the landing page. --}}
            <div class="catmore-text" data-catmore-text data-lenis-prevent tabindex="0" autofocus>{!! nl2br(e($gvLong)) !!}</div>
        </dialog>
    </div>
@endif
