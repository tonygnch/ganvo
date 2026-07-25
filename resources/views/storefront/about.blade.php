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

    // Only invite people to write to us if that page is actually published.
    $contactOn = $store->contactPage()['enabled'];
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    {{-- Styled with the legacy alias tokens only (--primary, --border, --text,
         --text-muted, --surface/--card, --bg), each with a literal fallback:
         a few themes (Menu) never declare the aliases at all, and this page is
         inherited by every theme that doesn't ship its own. --}}
    <style>
        .about-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }

        /* -------- Head -------- */
        .about-head { max-width: 68ch; margin-bottom: 3rem; }
        .about-since {
            display: block;
            margin-bottom: .75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--primary);
        }
        .about-head h1 {
            margin: 0 0 .75rem;
            font-size: clamp(2rem, 5vw, 3.25rem);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.02em;
        }
        .about-lead {
            margin: 0;
            font-size: clamp(1rem, 1.6vw, 1.1875rem);
            line-height: 1.6;
            color: var(--text-muted, #57534e);
        }

        .about-section + .about-section { margin-top: 3.5rem; }
        .about-section h2 {
            margin: 0 0 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--text-muted, #57534e);
        }

        /* -------- Story -------- */
        /* minmax(0, …) so a long unbroken Bulgarian word can't push the text
           column wider than its share of the grid. */
        .story-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
            gap: 2.5rem;
            align-items: start;
        }
        .story-grid.single { grid-template-columns: minmax(0, 1fr); max-width: 68ch; }
        .story-text p {
            margin: 0 0 1.25rem;
            font-size: 1.0625rem;
            line-height: 1.75;
            color: var(--text, #1c1917);
            /* keeps hard line breaks the merchant typed inside a paragraph */
            white-space: pre-line;
        }
        .story-text p:last-child { margin-bottom: 0; }
        /* the drop cap below is a float, and a one-line opening paragraph
           would otherwise let it hang down into the timeline */
        .story-text::after { content: ""; display: block; clear: both; }
        .story-text p:first-child::first-letter {
            float: left;
            margin: .08em .12em 0 0;
            font-size: 3.2em;
            line-height: .8;
            font-weight: 800;
            color: var(--primary);
        }
        .story-figure {
            margin: 0;
            aspect-ratio: 4 / 5;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid var(--border, #e7e5e4);
            background: var(--surface, var(--card, #fff));
        }
        .story-figure img { display: block; width: 100%; height: 100%; object-fit: cover; }

        /* -------- Milestones -------- */
        .timeline { position: relative; margin: 0; padding: 0 0 0 1.75rem; list-style: none; }
        .timeline::before {
            content: "";
            position: absolute;
            left: 5px;
            top: .4rem;
            bottom: .4rem;
            width: 1px;
            background: var(--border, #e7e5e4);
        }
        .tl-item { position: relative; padding-bottom: 2rem; }
        .tl-item:last-child { padding-bottom: 0; }
        .tl-item::before {
            content: "";
            position: absolute;
            left: -1.75rem;
            top: .45rem;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: var(--primary);
            /* the ring hides the rule where it passes behind the dot */
            box-shadow: 0 0 0 4px var(--surface, var(--card, #fff));
        }
        .tl-year {
            display: block;
            margin-bottom: .25rem;
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--primary);
        }
        .tl-title {
            margin: 0 0 .375rem;
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--text, #1c1917);
        }
        .tl-text {
            margin: 0;
            max-width: 62ch;
            font-size: 0.9375rem;
            line-height: 1.65;
            color: var(--text-muted, #57534e);
            white-space: pre-line;
        }

        /* -------- Stats -------- */
        /* auto-fit + minmax means 2, 3 or 5 figures all lay out sensibly, and
           a long Bulgarian label wraps inside its cell instead of stretching it. */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(190px, 100%), 1fr));
            gap: 1px;
            background: var(--border, #e7e5e4);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            overflow: hidden;
        }
        .stat {
            background: var(--surface, var(--card, #fff));
            padding: 1.75rem 1.5rem;
        }
        .stat-value {
            display: block;
            font-size: clamp(1.875rem, 4vw, 2.75rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.02em;
            color: var(--primary);
            overflow-wrap: anywhere;
        }
        .stat-label {
            display: block;
            margin-top: .5rem;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: var(--text-muted, #57534e);
        }

        /* -------- Gallery -------- */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr));
            gap: 1rem;
        }
        .gallery-grid figure {
            margin: 0;
            aspect-ratio: 3 / 2;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid var(--border, #e7e5e4);
            background: var(--surface, var(--card, #fff));
        }
        .gallery-grid img { display: block; width: 100%; height: 100%; object-fit: cover; }

        /* -------- Closing CTA -------- */
        .about-cta {
            margin-top: 3.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border, #e7e5e4);
        }
        .about-cta a {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            padding: .9375rem 1.75rem;
            border-radius: .75rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform .12s ease, box-shadow .15s ease;
        }
        .about-cta a:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -6px color-mix(in srgb, var(--primary) 50%, transparent);
        }

        @media (max-width: 900px) {
            .story-grid { grid-template-columns: minmax(0, 1fr); }
            /* the portrait crop is far too tall once it spans the full width */
            .story-figure { aspect-ratio: 3 / 2; }
        }
        @media (max-width: 560px) {
            .about-page { padding: 2rem 1.25rem 3rem; }
            .story-text p:first-child::first-letter { font-size: 2.6em; }
        }
    </style>

    <div class="about-page">
        <header class="about-head">
            @if ($about['founded_year'])
                <span class="about-since">{{ __('site.storefront.about.since', ['year' => $about['founded_year']]) }}</span>
            @endif
            <h1>{{ $heading }}</h1>
            <p class="about-lead">{{ $intro }}</p>
        </header>

        @if ($paragraphs)
            <section class="about-section">
                <div class="story-grid {{ $leadImage ? '' : 'single' }}">
                    <div class="story-text">
                        @foreach ($paragraphs as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                    @if ($leadImage)
                        <figure class="story-figure">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($leadImage) }}"
                                 alt="{{ __('site.storefront.about.image_alt', ['name' => $tenant->name, 'n' => 1]) }}"
                                 loading="lazy">
                        </figure>
                    @endif
                </div>
            </section>
        @endif

        @if ($about['milestones'])
            <section class="about-section">
                <h2>{{ __('site.storefront.about.milestones_h') }}</h2>
                <ol class="timeline">
                    @foreach ($about['milestones'] as $milestone)
                        <li class="tl-item">
                            @if ($milestone['year'])
                                <span class="tl-year">{{ $milestone['year'] }}</span>
                            @endif
                            @if ($milestone['title'])
                                <h3 class="tl-title">{{ $milestone['title'] }}</h3>
                            @endif
                            @if ($milestone['text'])
                                <p class="tl-text">{{ $milestone['text'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($about['stats'])
            <section class="about-section">
                <h2>{{ __('site.storefront.about.stats_h') }}</h2>
                <div class="stats-row">
                    @foreach ($about['stats'] as $stat)
                        <div class="stat">
                            <span class="stat-value">{{ $stat['value'] }}</span>
                            <span class="stat-label">{{ $stat['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($gallery)
            <section class="about-section">
                <h2>{{ __('site.storefront.about.gallery_h') }}</h2>
                <div class="gallery-grid">
                    @foreach ($gallery as $i => $image)
                        <figure>
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                                 alt="{{ __('site.storefront.about.image_alt', ['name' => $tenant->name, 'n' => $i + ($leadImage ? 2 : 1)]) }}"
                                 loading="lazy">
                        </figure>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($contactOn)
            <div class="about-cta">
                <a href="/contact">{{ __('site.storefront.about.cta') }}</a>
            </div>
        @endif
    </div>
@endsection
