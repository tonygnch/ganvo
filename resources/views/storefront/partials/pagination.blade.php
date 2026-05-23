@php
    /*
     | Storefront pagination — minimal numbered pager that works across
     | every theme without pulling in Tailwind. Themes include this
     | below their product grid.
     |
     | Input:
     |   $products  paginator (LengthAwarePaginator)
     |
     | Renders nothing when there's only one page. Uses .sp-pager-*
     | classes so themes can override colors via their own CSS.
     */
@endphp

@if ($products->hasPages())
    <nav class="sp-pager" role="navigation" aria-label="{{ __('site.storefront.pagination.aria_label') }}">
        <ul class="sp-pager-list">
            {{-- Prev --}}
            @if ($products->onFirstPage())
                <li class="sp-pager-item sp-pager-disabled" aria-hidden="true">
                    <span class="sp-pager-link">‹ {{ __('site.storefront.pagination.prev') }}</span>
                </li>
            @else
                <li class="sp-pager-item">
                    <a class="sp-pager-link" href="{{ $products->previousPageUrl() }}" rel="prev">‹ {{ __('site.storefront.pagination.prev') }}</a>
                </li>
            @endif

            {{-- Numbered links: window of pages around current; gaps as ellipsis. --}}
            @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                @if ($page == $products->currentPage())
                    <li class="sp-pager-item sp-pager-current" aria-current="page">
                        <span class="sp-pager-link">{{ $page }}</span>
                    </li>
                @else
                    <li class="sp-pager-item">
                        <a class="sp-pager-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($products->hasMorePages())
                <li class="sp-pager-item">
                    <a class="sp-pager-link" href="{{ $products->nextPageUrl() }}" rel="next">{{ __('site.storefront.pagination.next') }} ›</a>
                </li>
            @else
                <li class="sp-pager-item sp-pager-disabled" aria-hidden="true">
                    <span class="sp-pager-link">{{ __('site.storefront.pagination.next') }} ›</span>
                </li>
            @endif
        </ul>
    </nav>

    <style>
        .sp-pager { margin: 2.5rem 0 0; display: flex; justify-content: center; }
        .sp-pager-list {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: .375rem;
            margin: 0;
            padding: 0;
        }
        .sp-pager-item { display: inline-flex; }
        .sp-pager-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.25rem;
            height: 2.25rem;
            padding: 0 .75rem;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 6px;
            font-size: .8125rem;
            font-weight: 500;
            color: inherit;
            text-decoration: none;
            background: rgba(255, 255, 255, .6);
            transition: background-color .12s ease, border-color .12s ease;
        }
        .sp-pager-link:hover {
            background: rgba(0, 0, 0, .06);
            border-color: rgba(0, 0, 0, .35);
        }
        .sp-pager-current .sp-pager-link {
            background: #111;
            color: white;
            border-color: #111;
            cursor: default;
        }
        .sp-pager-disabled .sp-pager-link {
            opacity: .4;
            cursor: not-allowed;
        }
    </style>
@endif
