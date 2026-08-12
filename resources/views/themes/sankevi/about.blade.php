{{-- Sankevi — the history page. The one place where the specs step aside
     entirely: the story runs down the page in the serif, the years stand in
     the margin like dates burnt into a beam, and the photographs are cut at
     the corner the way every plate on this storefront is. --}}
@php
    $title = __('site.storefront.about.title');

    // Blank heading/intro mean the merchant never overrode them — fall back to
    // the platform copy rather than rendering an empty page head.
    $heading = $about['heading'] ?: __('site.storefront.about.heading');
    $intro = $about['intro'] ?: __('site.storefront.about.intro');

    // Merchants write the story in one box with blank lines between
    // paragraphs; single newlines inside a paragraph are kept by CSS.
    $paragraphs = collect(preg_split('/\R{2,}/', $about['story']))
        ->map(fn ($p) => trim($p))
        ->filter()
        ->all();

    // The first photo only earns a spot beside the story when there IS a
    // story to sit beside; otherwise every image goes to the gallery.
    $leadImage = $paragraphs ? ($about['images'][0] ?? null) : null;
    $gallery = $leadImage ? array_slice($about['images'], 1) : $about['images'];

    // THE HERITAGE BAND, which used to open the landing page. The merchant
    // asked for the family and the history to be told in one place instead of
    // half here and half there, so the workshop photograph and the manifesto
    // came across with the story they always belonged to. It is the theme's
    // own slot + copy, so a merchant who has already replaced either keeps
    // exactly what they wrote.
    //
    // The shipped workshop photograph carries a printed white mount that has
    // no business on a bark ground, so the default asset is zoomed past its
    // border; a merchant's own upload is shown exactly as uploaded.
    $workshopUrl = $theme->on('story_band') ? $theme->image('story_image') : null;
    $workshopIsShipped = $workshopUrl && str_contains($workshopUrl, '/images/demo/sankevi/workshop');

    // Only invite people to write to us if that page is actually published.
    $contactOn = $store->contactPage()['enabled'];
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        /* padding-BOTTOM only. This class sits on the same element as .wrap,
           and the `padding: 0 0 Npx` shorthand was resetting .wrap's own
           `0 40px` horizontal gutter to zero. On a desktop the wrap is
           centred inside a 1280px max-width so nothing touched the edge
           and it went unnoticed; on a 390px phone the wrap IS the
           viewport, so every line ran flush into both edges. */
        .about { padding-bottom: 40px; }

        .ab-story { display: grid; grid-template-columns: 1.02fr .98fr; gap: 0; align-items: start; margin-top: 64px; }
        .ab-story.single { grid-template-columns: 1fr; max-width: 68ch; }
        .ab-story .tx p { color: var(--muted); font-size: 16.5px; line-height: 1.8; margin-bottom: 20px; max-width: 46ch; white-space: pre-line; }
        /* the first paragraph carries the weight — set in the serif, larger */
        .ab-story .tx p:first-child { font-family: var(--display); font-weight: 400; font-size: clamp(21px, 2.2vw, 27px); line-height: 1.45; color: var(--txt); }
        .ab-story figure { position: relative; margin-left: -10%; margin-top: 34px; aspect-ratio: 4 / 5; overflow: hidden; background: var(--surface2); }
        .ab-story.single figure { display: none; }
        .ab-story figure img { width: 100%; height: 100%; object-fit: cover; }

        /* ===== the heritage band — photograph, then the manifesto over it,
           the same overlap the landing page used to open with ===== */
        .heritage { position: relative; display: grid; grid-template-columns: 1.02fr .98fr; align-items: center; margin-top: 96px; }
        .heritage .art { position: relative; aspect-ratio: 4 / 3; overflow: hidden; background: var(--surface2); }
        .heritage .art img { width: 100%; height: 100%; object-fit: cover; }
        .heritage .art img.mounted { transform: scale(1.14); }
        .heritage .tx { position: relative; z-index: 2; margin-left: -13%; background: var(--surface); border: 1px solid var(--line); padding: clamp(34px, 4.4vw, 62px); }
        .heritage .tx .k { display: block; margin-bottom: 18px; }
        .heritage .tx h2 { font-family: var(--display); font-weight: 500; font-size: clamp(26px, 3.1vw, 41px); line-height: 1.03; letter-spacing: 0; margin-bottom: 22px; }
        .heritage .tx h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .heritage .tx p { color: var(--muted); max-width: 46ch; font-size: 15.5px; }
        /* No photograph in the slot: the manifesto is a pull-quote instead of
           half of an overlap, and centres on its own. */
        .heritage.solo { grid-template-columns: minmax(0, 1fr); }
        .heritage.solo .tx { margin-left: 0; }

        .ab-sec { margin-top: 104px; }
        .ab-sec > h2 { font-family: var(--display); font-weight: 500; font-size: clamp(25px, 3vw, 38px); line-height: 1.04; letter-spacing: 0; padding-bottom: 18px; margin-bottom: 34px; border-bottom: 1px solid var(--line); }

        /* ===== the years, standing in the margin ===== */
        .tl { list-style: none; }
        .tl li { display: grid; grid-template-columns: 180px 1fr; gap: 34px; padding: 30px 0; border-bottom: 1px solid var(--line); }
        .tl .tl-year { font-family: var(--display); font-weight: 500; font-size: 28px; line-height: 1.1; color: var(--accent-ink); font-variant-numeric: tabular-nums; }
        .tl .tl-title { font-family: var(--display); font-weight: 500; font-size: 25px; line-height: 1.15; margin-bottom: 10px; }
        .tl .tl-text { color: var(--muted); max-width: 60ch; }

        /* ===== the numbers ===== */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 24px; }
        .stats .stat { background: var(--surface); border: 1px solid var(--line); padding: 30px 26px; }
        .stats .stat-value { display: block; font-family: var(--display); font-weight: 500; font-size: 44px; line-height: 1; letter-spacing: 0; font-variant-numeric: tabular-nums; }
        .stats .stat-label { display: block; margin-top: 12px; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }

        /* ===== the plates ===== */
        .gal { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 22px; }
        .gal figure { aspect-ratio: 3 / 4; overflow: hidden; background: var(--surface2); }
        .gal figure img { width: 100%; height: 100%; object-fit: cover; transition: transform 1.2s cubic-bezier(.19, .74, .16, 1); }
        .gal figure:hover img { transform: scale(1.04); }
        @media (prefers-reduced-motion: reduce) { .gal figure:hover img { transform: none; } }
        }

        .ab-cta { margin-top: 84px; text-align: center; }

        @media (max-width: 1100px) { .heritage .tx { margin-left: -8%; } }
        @media (max-width: 900px) {
            .heritage { grid-template-columns: 1fr; margin-top: 74px; }
            .heritage .tx { margin-left: 0; margin-top: -44px; width: 93%; }
            .ab-story { grid-template-columns: 1fr; }
            .ab-story figure { margin-left: 0; margin-top: 34px; aspect-ratio: 4 / 3; }
            .tl li { grid-template-columns: 1fr; gap: 10px; padding: 24px 0; }
            .ab-sec { margin-top: 74px; }
        }
        @media (max-width: 620px) { .heritage .tx { width: 100%; } }
    </style>

    <main>
        <div class="wrap about">
            <div class="page-head reveal">
                @if ($theme->on('gutter_index'))
                    <span class="gx" aria-hidden="true" style="top: 60px;">{{ $theme->copy('story_eyebrow') }}</span>
                @endif
                @if ($about['founded_year'])
                    <div class="crumb">{{ __('site.storefront.about.since', ['year' => $about['founded_year']]) }}</div>
                @else
                    <div class="crumb">{{ $theme->copy('story_eyebrow') }}</div>
                @endif
                <h1>{{ $heading }}</h1>
                <p>{{ $intro }}</p>
            </div>

            @if ($paragraphs)
                <section class="ab-story {{ $leadImage ? '' : 'single' }} reveal">
                    <div class="tx">
                        @foreach ($paragraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                    @if ($leadImage)
                        <figure class="cut cut-lg">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($leadImage) }}"
                                 alt="{{ __('site.storefront.about.image_alt', ['name' => $tenant->name, 'n' => 1]) }}"
                                 loading="lazy">
                        </figure>
                    @endif
                </section>
            @endif

            @if ($theme->on('story_band'))
                <section class="heritage {{ $workshopUrl ? '' : 'solo' }} reveal">
                    @if ($workshopUrl)
                        <div class="art cut cut-lg">
                            <img class="{{ $workshopIsShipped ? 'mounted' : '' }}" src="{{ $workshopUrl }}" alt="" loading="lazy">
                        </div>
                    @endif
                    <div class="tx">
                        <h2>{!! $theme->copy('story_h2_html') !!}</h2>
                        <p>{{ $theme->copy('story_body') }}</p>
                    </div>
                </section>
            @endif

            @if ($about['milestones'])
                <section class="ab-sec reveal">
                    <h2>{{ __('site.storefront.about.milestones_h') }}</h2>
                    <ol class="tl">
                        @foreach ($about['milestones'] as $milestone)
                            <li>
                                <span class="tl-year">{{ $milestone['year'] }}</span>
                                <div>
                                    @if ($milestone['title'])<h3 class="tl-title">{{ $milestone['title'] }}</h3>@endif
                                    @if ($milestone['text'])<p class="tl-text">{{ $milestone['text'] }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($about['stats'])
                <section class="ab-sec reveal">
                    <h2>{{ __('site.storefront.about.stats_h') }}</h2>
                    <div class="stats">
                        @foreach ($about['stats'] as $stat)
                            <div class="stat cut">
                                <span class="stat-value">{{ $stat['value'] }}</span>
                                <span class="stat-label">{{ $stat['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($gallery)
                <section class="ab-sec reveal">
                    <h2>{{ __('site.storefront.about.gallery_h') }}</h2>
                    <div class="gal">
                        @foreach ($gallery as $i => $image)
                            <figure class="cut cut-lg">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                                     alt="{{ __('site.storefront.about.image_alt', ['name' => $tenant->name, 'n' => $i + ($leadImage ? 2 : 1)]) }}"
                                     loading="lazy">
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($contactOn)
                <div class="ab-cta reveal">
                    <a class="btn" href="/contact">{{ __('site.storefront.about.cta') }}</a>
                </div>
            @endif
        </div>
    </main>
@endsection
