@php $title = __('site.onboarding.theme.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .theme-card {
            border: 2px solid var(--hair);
            border-radius: 1rem;
            background: var(--surface);
            overflow: hidden;
            cursor: pointer;
            transition: border-color .15s ease, transform .12s ease, box-shadow .15s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .theme-card:hover { border-color: var(--text-muted); transform: translateY(-2px); }
        .theme-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        /* Selection styling driven purely by :has(:checked) — see plan.blade
           for the same rationale. */
        .theme-card:has(input[type="radio"]:checked) {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 18%, transparent);
        }

        .theme-preview {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: var(--muted);
            border-bottom: 1px solid var(--hair);
            position: relative;
            overflow: hidden;
        }
        /* Scale a desktop-width render down into the small preview area. */
        .theme-preview iframe {
            width: 1200px;
            height: 900px;
            border: 0;
            transform: scale(0.4);
            transform-origin: 0 0;
            pointer-events: none;
        }
        .theme-preview .placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft);
            font-size: 0.8125rem;
            letter-spacing: 0.08em;
            background: linear-gradient(180deg, var(--muted) 0%, var(--surface) 100%);
        }

        .theme-meta {
            padding: 1.25rem 1.5rem 1.5rem;
        }
        .theme-meta h3 {
            margin: 0 0 .375rem;
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .theme-meta p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .theme-meta .selected-flag {
            display: none;
            margin-top: .875rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-strong);
        }
        .theme-card:has(input[type="radio"]:checked) .selected-flag { display: block; }

        @media (max-width: 760px) {
            .theme-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="panel wide">
        <p class="panel-eyebrow">{{ __('site.onboarding.theme.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.theme.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.theme.lead') }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/theme">
            @csrf

            <div class="theme-grid">
                @foreach ($themes as $key => $meta)
                    @php $isSelected = old('theme', $tenant->store->theme ?? 'default') === $key; @endphp
                    <label class="theme-card">
                        <input type="radio" name="theme" value="{{ $key }}" @if($isSelected) checked @endif required>
                        <div class="theme-preview">
                            <iframe src="/onboarding/theme/preview/{{ $key }}" loading="lazy" title="{{ $meta['name'] }} preview"></iframe>
                        </div>
                        <div class="theme-meta">
                            <h3>{{ $meta['name'] }}</h3>
                            <p>{{ $meta['description'] }}</p>
                            <p class="selected-flag">✓ {{ __('site.onboarding.theme.selected') }}</p>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="actions">
                <a href="/onboarding/plan" class="btn btn-ghost">← {{ __('site.onboarding.theme.back') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('site.onboarding.theme.cta') }} →</button>
            </div>
        </form>
    </div>
@endsection
