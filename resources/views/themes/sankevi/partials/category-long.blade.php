{{--
 | The category's full description — ONE copy of the text, presented two ways.
 |
 | A desktop has the room to simply print it, so it is printed. A phone does
 | not, and a long block above the goods pushes the goods off the screen, so
 | there it is a button that opens the text over the page.
 |
 | The element is a <dialog> in both cases. On a phone that buys the whole
 | modal contract from the browser — focus held inside, Escape closes, the rest
 | of the page inert, a real backdrop — none of which is worth hand-writing.
 | On a desktop the stylesheet simply overrides the closed dialog's display and
 | it lays out as an ordinary block, so the prose exists once in the markup and
 | once in the accessibility tree rather than being duplicated per breakpoint.
 |
 | Expects $catLong (the text) and $catName.
--}}
@php($gvLong = trim((string) ($catLong ?? '')))
@if ($gvLong !== '')
    <div class="catmore">
        <button type="button" class="catmore-btn" data-catmore>
            {{ __('site.storefront.controls.category_more') }}
        </button>

        <dialog class="catmore-panel" data-catmore-panel
                aria-label="{{ $catName ?? __('site.storefront.controls.category') }}">
            <button type="button" class="catmore-close" data-catmore-close
                    aria-label="{{ __('site.storefront.product.close') }}">✕</button>
            <h2 class="catmore-h">{{ $catName ?? '' }}</h2>
            <div class="catmore-text">{!! nl2br(e($gvLong)) !!}</div>
        </dialog>
    </div>
@endif
