{{--
    Horizontal scroll-driven PROCESS timeline (used for the "how we work" section).
    The section pins and a horizontal track of phase "stations" scrubs sideways as
    you scroll: each station arrives at a centred playhead, a spine line draws itself
    to that point, the node ignites, and a live folio counts 01 → NN. Cards zig-zag
    above / below the spine; a giant ghost numeral sits behind each for depth.

    Fallback: no-JS / touch-narrow / prefers-reduced-motion / .is-static all collapse
    to a plain vertical numbered list (every phase visible) — nothing is ever hidden.
    Driven by marketing.js buildTimeline() / timelinePin().

    Params: $id, $eyebrow, $heading, $items (each ['title' => , 'body' => ]).
--}}
@php $total = str_pad(count($items), 2, '0', STR_PAD_LEFT); @endphp
<section class="section timeline-section" id="{{ $id }}">
    <div class="timeline" data-timeline style="--n: {{ count($items) }}">
        <div class="tl__inner">
            <header class="tl__head">
                <div class="tl__head-lead">
                    <p class="eyebrow" data-reveal="fade">{{ $eyebrow }}</p>
                    <h2 class="tl__heading" data-reveal="up">{{ $heading }}</h2>
                </div>
                <div class="tl__readout" aria-hidden="true">
                    <span class="tl__folio">
                        <span class="tl__folio-cur" data-tl-current>01</span><span class="tl__folio-sep">/</span><span class="tl__folio-total">{{ $total }}</span>
                    </span>
                    <span class="tl__meter"><span class="tl__meter-fill" data-tl-meter></span></span>
                </div>
            </header>

            <div class="tl__viewport">
                <div class="tl__track" data-tl-track>
                    <div class="tl__spine" data-tl-spine aria-hidden="true"><span class="tl__spine-fill" data-tl-fill></span></div>
                    <ol class="tl__stations" role="list">
                        @foreach ($items as $i => $it)
                            <li class="tl__station @if($i === 0) is-active @endif" data-tl-station>
                                <span class="tl__ghost" aria-hidden="true">{{ $i + 1 }}</span>
                                <span class="tl__stem" aria-hidden="true"></span>
                                <span class="tl__node" aria-hidden="true"></span>
                                <div class="tl__card">
                                    <p class="tl__index" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}<span class="tl__index-total"> / {{ $total }}</span></p>
                                    <h3 class="tl__title">{{ $it['title'] }}</h3>
                                    <p class="tl__body">{{ $it['body'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <span class="tl__playhead" aria-hidden="true"></span>
            </div>
        </div>
    </div>
</section>
