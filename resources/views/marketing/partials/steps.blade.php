{{--
    Scroll-driven "one at a time" stepper, laid out as a two-column editorial
    spread: a left-anchored section masthead (eyebrow · heading · folio counter ·
    progress ticks) sits on the same page grid as the stepping content on the
    right, so the title reads as a structural anchor rather than a floating label.
    The section holds (GSAP pin) while you scroll through it; a single item shows
    on the right and swaps to the next, one after another — index + folio driven by
    scroll progress in marketing.js buildSteps().

    Fallback: on touch / narrow screens / prefers-reduced-motion the CSS collapses
    to a single column and renders a plain stacked list (all items visible), so
    nothing is hidden without the scroll interaction.

    Params: $id, $eyebrow, $heading, $items (each ['title' => , 'body' => ]).
--}}
@php $total = str_pad(count($items), 2, '0', STR_PAD_LEFT); @endphp
<section class="section steps-section" id="{{ $id }}">
    <div class="wrap steps" data-steps style="--n: {{ count($items) }}">
        <div class="steps__inner">
            <header class="steps__masthead">
                <p class="eyebrow" data-reveal="fade">{{ $eyebrow }}</p>
                <h2 class="steps__heading" data-reveal="up">{{ $heading }}</h2>
                <div class="steps__progress" aria-hidden="true">
                    <span class="steps__folio">
                        <span class="steps__folio-cur" data-step-current>01</span><span class="steps__folio-sep">/</span><span class="steps__folio-total">{{ $total }}</span>
                    </span>
                    <span class="steps__dots">
                        @foreach ($items as $i => $it)<span class="steps__dot @if($i === 0) is-active @endif"></span>@endforeach
                    </span>
                </div>
            </header>
            <div class="steps__stage">
                @foreach ($items as $i => $it)
                    <article class="step @if($i === 0) is-active @endif" data-step>
                        <p class="step__no" aria-hidden="true">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}<span class="step__no-total"> / {{ $total }}</span></p>
                        <h3 class="step__title">{{ $it['title'] }}</h3>
                        <p class="step__body">{{ $it['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
