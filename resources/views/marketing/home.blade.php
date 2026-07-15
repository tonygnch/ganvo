<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.add('js');</script>
    @include('partials.favicon')
    <title>{{ $cs['page_title'] ?? __('site.marketing.title') }}</title>
    <meta name="description" content="{{ $cs['meta_description'] ?? __('site.marketing.meta_description') }}">
    @include('partials.social-meta', [
        'title'       => $cs['page_title'] ?? __('site.marketing.title'),
        'description' => $cs['meta_description'] ?? __('site.marketing.meta_description'),
    ])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=unbounded:400,500,600|manrope:400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet">
    @vite(['resources/css/marketing.css', 'resources/js/marketing.js'])
</head>
<body>
@php
    $currentLocale = app()->getLocale();
    $languages     = \App\Http\Middleware\SetLocale::available();

    $services = __('site.marketing.services.items');
    $scenes   = __('site.marketing.work.scenes');
    $why      = __('site.marketing.why.items');
    $steps    = __('site.marketing.process.steps');
    $types    = __('site.marketing.contact.types');
    $budgets  = __('site.marketing.contact.budgets');

    $cEmail = $cs['contact_email'] ?? __('site.marketing.contact.email');
    $cPhone = $cs['contact_phone'] ?? '';
    $cIg    = $cs['contact_instagram'] ?? '';
    $cFb    = $cs['contact_facebook'] ?? '';

    // Sections for the "you are here" status rail (id ⇒ label).
    // 'studio' (Why Ganvo) is temporarily hidden below — omitted here too so the
    // rail doesn't show a dead tick. Re-add the line to bring it back.
    $rail = [
        'top'      => __('site.marketing.sections.home'),
        'services' => __('site.marketing.sections.services'),
        // 'studio'   => __('site.marketing.sections.studio'),
        'process'  => __('site.marketing.sections.process'),
        'work'     => __('site.marketing.sections.work'),
        'contact'  => __('site.marketing.sections.contact'),
    ];
@endphp

    {{-- Cinematic atmosphere: film grain, edge vignette, ambient blue glow. --}}
    <div class="fx-grain" aria-hidden="true"></div>
    <div class="fx-vignette" aria-hidden="true"></div>
    <div class="fx-glow" aria-hidden="true"></div>

    {{-- HUD viewport frame — luminous corner brackets + status readout. --}}
    <div class="fx-hud" aria-hidden="true">
        <i></i><i></i><i></i><i></i>
        <span class="fx-hud__status">GANVO · <b>SIGNAL</b> · 42.69°N 23.32°E</span>
    </div>

    <div class="m-progress" aria-hidden="true"></div>

    {{-- ─── Section status rail — "you are here" (desktop) ──────────────────── --}}
    <nav class="section-rail" data-rail aria-label="{{ __('site.marketing.sections.home') }}">
        @php $ri = 0; @endphp
        @foreach ($rail as $id => $label)
            <a href="#{{ $id }}" class="rail-item @if($ri===0) is-active @endif" data-rail-item="{{ $id }}">
                <span class="rail-item__no">{{ str_pad(++$ri, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="rail-item__tick" aria-hidden="true"></span>
                <span class="rail-item__label">{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    {{-- ─── Nav ─────────────────────────────────────────────────────────── --}}
    <nav class="m-nav">
        <a href="#top" class="m-nav__brand" aria-label="Ganvo — home"><img class="m-nav__logo" src="{{ asset('images/brand/logo-full-white.png') }}" alt="Ganvo" width="98" height="22"></a>

        <div class="m-nav__right">
            <details class="lang">
                <summary>{{ strtoupper($currentLocale) }} <svg width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg></summary>
                <div class="lang__menu" role="menu">
                    @foreach ($languages as $code => $name)
                        <a role="menuitem" href="{{ route('lang.switch', ['locale' => $code]) }}" class="@if($currentLocale===$code) active @endif">
                            <span>{{ $name }}</span>
                            <svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg>
                        </a>
                    @endforeach
                </div>
            </details>
            <a href="#contact" class="btn btn--primary btn--sm">{{ __('site.marketing.nav.book') }}</a>
        </div>
    </nav>

    {{-- ─── Hero ────────────────────────────────────────────────────────── --}}
    <header class="hero" id="top" data-hero>
        <div class="hero__media" data-hero-media>
            {{-- Cinematic loop; hero.png is the poster so the frame is instant and
                 stands in if the video is unsupported or reduced-motion is on. --}}
            <video class="hero__video" autoplay muted loop playsinline preload="auto"
                   poster="{{ asset('images/marketing/v2/hero.png') }}" aria-hidden="true" data-hero-video>
                <source src="{{ asset('images/marketing/v2/hero.mp4') }}" type="video/mp4">
            </video>
        </div>
        <div class="hero__veil" aria-hidden="true"></div>

        <div class="wrap hero__inner" data-hero-content>
            <p class="hero__kicker">{{ $cs['hero_kicker'] ?? __('site.marketing.hero.kicker') }}</p>
            <h1 class="hero__title">{{ $cs['hero_headline'] ?? __('site.marketing.hero.headline') }}
                <span class="accent">{{ $cs['hero_headline_accent'] ?? __('site.marketing.hero.headline_accent') }}</span></h1>
            <p class="hero__sub">{{ $cs['hero_sub'] ?? __('site.marketing.hero.sub') }}</p>
            <div class="hero__cta">
                <a href="#contact" class="btn btn--primary">{{ $cs['hero_cta_primary'] ?? __('site.marketing.hero.cta_primary') }} <span class="btn__arrow" aria-hidden="true">→</span></a>
                <a href="#work" class="btn btn--ghost">{{ $cs['hero_cta_secondary'] ?? __('site.marketing.hero.cta_secondary') }}</a>
            </div>
        </div>

        <div class="hero__cue" data-hero-cue aria-hidden="true">
            <span class="rail"></span>{{ __('site.marketing.hero.cue') }}
        </div>

        {{-- signal strip — quiet mono facts; the first "easy contact" cue --}}
        <div class="hero__meta" aria-hidden="true">
            <span><b>EST 2024</b> · {{ __('site.marketing.sections.studio') }}</span>
            <span class="dot"></span>
            <span>{{ __('site.marketing.contact.assurances.0') }}</span>
        </div>
    </header>

    {{-- ─── Statement ───────────────────────────────────────────────────── --}}
    <section class="wrap statement" data-statement-zoom data-hold>
        <p class="statement__text" data-split>{{ $cs['statement'] ?? __('site.marketing.statement') }}</p>
    </section>

    {{-- ─── Services (scroll stepper) ───────────────────────────────────── --}}
    @include('marketing.partials.steps', [
        'id'      => 'services',
        'eyebrow' => $cs['services_eyebrow'] ?? __('site.marketing.services.eyebrow'),
        'heading' => $cs['services_heading'] ?? __('site.marketing.services.heading'),
        'items'   => $services,
    ])

    {{-- ─── Why Ganvo (scroll stepper) — temporarily hidden.
         To bring it back: uncomment this block AND the 'studio' line in the
         $rail array above. --}}
    {{--
    @include('marketing.partials.steps', [
        'id'      => 'studio',
        'eyebrow' => $cs['why_eyebrow'] ?? __('site.marketing.why.eyebrow'),
        'heading' => $cs['why_heading'] ?? __('site.marketing.why.heading'),
        'items'   => $why,
    ])
    --}}

    {{-- ─── Process (horizontal scroll-driven timeline) ─────────────────── --}}
    @include('marketing.partials.timeline', [
        'id'      => 'process',
        'eyebrow' => $cs['process_eyebrow'] ?? __('site.marketing.process.eyebrow'),
        'heading' => $cs['process_heading'] ?? __('site.marketing.process.heading'),
        'items'   => $steps,
    ])

    {{-- ─── Selected work — opens the projects modal ───────────────────────── --}}
    <section class="section work" id="work" data-hold>
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow" data-reveal="fade">{{ $cs['work_eyebrow'] ?? __('site.marketing.work.eyebrow') }}</p>
                <h2 class="h2" data-reveal="up">{{ $cs['work_heading'] ?? __('site.marketing.work.heading') }}</h2>
                <p class="work__lead" data-reveal="up">{{ $cs['work_lead'] ?? __('site.marketing.work.lead') }}</p>
            </div>
            <button type="button" class="work-open" data-work-open aria-haspopup="dialog" aria-controls="work-modal" data-reveal="up">
                <span class="work-open__label">{{ __('site.marketing.work.open') }}</span>
                <span class="work-open__cta">
                    <span class="work-open__count">{{ __('site.marketing.work.count', ['count' => count($scenes)]) }}</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17L17 7M17 7H8M17 7v9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </button>
        </div>
    </section>

    {{-- Recent-projects modal — opened from the Work section; keyboard-accessible
         (Esc / focus-trap / restore focus). --}}
    <div class="work-modal" id="work-modal" data-work-modal hidden>
        <div class="work-modal__backdrop" data-work-close></div>
        <div class="work-modal__panel" role="dialog" aria-modal="true" aria-labelledby="work-modal-title" tabindex="-1">
            <div class="work-modal__head">
                <div>
                    <p class="eyebrow">{{ $cs['work_eyebrow'] ?? __('site.marketing.work.eyebrow') }}</p>
                    <h2 id="work-modal-title" class="work-modal__title">{{ $cs['work_heading'] ?? __('site.marketing.work.heading') }}</h2>
                </div>
                <button type="button" class="work-modal__close" data-work-close aria-label="{{ __('site.marketing.work.close') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="proj-list" data-proj-list>
                @foreach ($scenes as $i => $scene)
                    @php
                        $num = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                        $url = $scene['url'] ?? ($siteUrls[$scene['slug']] ?? '#');
                    @endphp
                    <a class="proj" href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                       data-proj data-url="{{ $url }}"
                       aria-label="{{ $scene['name'] }} — {{ $scene['type'] }} ({{ __('site.marketing.work.visit') }})">
                        <span class="proj__index">{{ $num }}</span>
                        <span class="proj__name">{{ $scene['name'] }}</span>
                        <span class="proj__type">{{ $scene['type'] }}</span>
                        @if (!empty($scene['tagline']))
                            <span class="proj__tagline">{{ $scene['tagline'] }}</span>
                        @endif
                        <span class="proj__go">{{ __('site.marketing.work.visit') }}
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17L17 7M17 7H8M17 7v9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Floating live preview (desktop hover) — layered above the modal panel. --}}
        <div class="proj-preview" data-proj-preview aria-hidden="true">
            <div class="proj-preview__frame">
                <iframe title="" tabindex="-1" scrolling="no" loading="lazy" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin"></iframe>
            </div>
            <span class="proj-preview__hint">{{ __('site.marketing.work.visit') }} ↗</span>
        </div>
    </div>

    {{-- ─── Contact / Book a call ───────────────────────────────────────── --}}
    <section class="section contact" id="contact">
        <div class="contact__bg" aria-hidden="true"><img src="{{ asset('images/marketing/v2/horizon.png') }}" alt="" loading="lazy"></div>
        <div class="contact__veil" aria-hidden="true"></div>
        <div class="wrap">
            <div class="contact__grid">
                <div class="contact__pitch">
                    <p class="eyebrow" data-reveal="fade">{{ $cs['contact_eyebrow'] ?? __('site.marketing.contact.eyebrow') }}</p>
                    <h2 class="h2 contact__title" data-reveal="up">{{ $cs['contact_heading'] ?? __('site.marketing.contact.heading') }}</h2>
                    <p class="contact__sub" data-reveal="up">{{ $cs['contact_sub'] ?? __('site.marketing.contact.sub') }}</p>

                    <ul class="contact__assurances" data-reveal="up" data-reveal-delay="0.06">
                        @foreach (__('site.marketing.contact.assurances') as $point)
                            <li>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if ($cEmail || $cPhone || $cIg || $cFb)
                        <div class="direct" data-reveal="up" data-reveal-delay="0.12">
                            <p class="direct__label">{{ __('site.marketing.contact.direct') }}</p>
                            <div class="direct__list">
                                @if ($cEmail)
                                    <a class="direct__item" href="mailto:{{ $cEmail }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                                        <span>{{ $cEmail }}</span>
                                    </a>
                                @endif
                                @if ($cPhone)
                                    <a class="direct__item" href="tel:{{ preg_replace('/[^0-9+]/', '', $cPhone) }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L20 13l1 4v0a2 2 0 01-2 2A16 16 0 013 5a2 2 0 012-1z"/></svg>
                                        <span>{{ $cPhone }}</span>
                                    </a>
                                @endif
                                @if ($cIg)
                                    <a class="direct__item" href="{{ $cIg }}" target="_blank" rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.6" fill="currentColor"/></svg>
                                        <span>Instagram</span>
                                    </a>
                                @endif
                                @if ($cFb)
                                    <a class="direct__item" href="{{ $cFb }}" target="_blank" rel="noopener noreferrer">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3a4 4 0 00-4 4v2H7v4h3v6h4v-6h3l1-4h-4V8a1 1 0 011-1z"/></svg>
                                        <span>Facebook</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <form class="form form-card" method="POST" action="{{ route('marketing.contact') }}" data-inquiry data-reveal="up" data-reveal-delay="0.08">
                    @csrf
                    <div class="hp" aria-hidden="true">
                        <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="form__row">
                        <div class="field">
                            <label for="f-name">{{ __('site.marketing.contact.form.name') }}</label>
                            <input id="f-name" type="text" name="name" required maxlength="120" value="{{ old('name') }}" autocomplete="name" placeholder="Jane Cooper">
                        </div>
                        <div class="field">
                            <label for="f-email">{{ __('site.marketing.contact.form.email') }}</label>
                            <input id="f-email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="email" placeholder="jane@company.com">
                        </div>
                    </div>

                    <div class="form__row">
                        <div class="field">
                            <label for="f-type">{{ __('site.marketing.contact.form.project_type') }}</label>
                            <select id="f-type" name="project_type">
                                <option value="">{{ __('site.marketing.contact.form.choose') }}</option>
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}" @selected(old('project_type')===$key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="f-budget">{{ __('site.marketing.contact.form.budget') }}</label>
                            <select id="f-budget" name="budget">
                                <option value="">{{ __('site.marketing.contact.form.choose') }}</option>
                                @foreach ($budgets as $key => $label)
                                    <option value="{{ $key }}" @selected(old('budget')===$key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="f-company">{{ __('site.marketing.contact.form.company') }}</label>
                        <input id="f-company" type="text" name="company" maxlength="160" value="{{ old('company') }}" autocomplete="organization" placeholder="Company Inc.">
                    </div>

                    <div class="field">
                        <label for="f-message">{{ __('site.marketing.contact.form.message') }}</label>
                        <textarea id="f-message" name="message" required minlength="10" maxlength="4000" placeholder="What are you building, and what does success look like?">{{ old('message') }}</textarea>
                    </div>

                    @if (session('inquiry_status') === 'ok')
                        <p class="form__note is-ok is-shown" data-inquiry-note role="status">{{ __('site.marketing.contact.thanks') }}</p>
                    @elseif (session('inquiry_error'))
                        <p class="form__note is-err is-shown" data-inquiry-note role="status">{{ session('inquiry_error') }}</p>
                    @else
                        <p class="form__note" data-inquiry-note role="status" aria-live="polite"></p>
                    @endif

                    <button type="submit" class="btn btn--primary form__submit" data-inquiry-submit data-sending="{{ __('site.marketing.contact.form.sending') }}">{{ __('site.marketing.contact.form.submit') }} <span class="btn__arrow" aria-hidden="true">→</span></button>
                </form>
            </div>
        </div>
    </section>

    {{-- ─── Footer ──────────────────────────────────────────────────────── --}}
    <footer class="m-footer">
        <div class="wrap m-footer__grid">
            <div>
                <img class="m-footer__logo" src="{{ asset('images/brand/logo-full-white.png') }}" alt="Ganvo" width="89" height="20">
                <p class="m-footer__tag">{{ __('site.marketing.footer.tagline') }}</p>
            </div>
            <div class="m-footer__meta">
                <span>© {{ date('Y') }} Ganvo</span>
            </div>
        </div>
    </footer>
</body>
</html>
