@php $title = __('site.onboarding.launched.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        /* Pure-CSS confetti — 50 falling specks of two accent colors. */
        .confetti {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .confetti span {
            position: absolute;
            top: -10vh;
            width: 8px;
            height: 14px;
            opacity: 0;
            animation: fall 3.5s linear forwards;
        }
        @keyframes fall {
            0%   { opacity: 0;  transform: translateY(0)        rotate(0deg); }
            10%  { opacity: 1; }
            100% { opacity: 0;  transform: translateY(110vh)    rotate(720deg); }
        }

        .lw-stage {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 640px;
            width: 100%;
            padding: 2rem;
        }
        .lw-hooray {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--primary-strong);
            margin: 0 0 1rem;
        }
        .lw-title {
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 1rem;
            line-height: 1.1;
        }
        .lw-lead {
            color: var(--text-muted);
            font-size: 1rem;
            margin: 0 0 2rem;
        }
        .lw-url-card {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.25rem;
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 9999px;
            box-shadow: 0 12px 32px -12px rgba(0,0,0,.1);
            margin: 0 0 2rem;
            max-width: 100%;
            overflow: hidden;
        }
        .lw-url-card .label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .lw-url-card .url {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.875rem;
            color: var(--text);
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }
        .lw-actions {
            display: flex;
            gap: .75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-big {
            padding: 1rem 1.75rem;
            font-size: 0.9375rem;
        }
    </style>

    <div class="confetti" aria-hidden="true">
        @for ($i = 0; $i < 50; $i++)
            <span style="
                left: {{ rand(0, 100) }}%;
                background: {{ $i % 2 === 0 ? '#2563EB' : '#1F2937' }};
                animation-delay: {{ rand(0, 3000) / 1000 }}s;
                animation-duration: {{ rand(3000, 5500) / 1000 }}s;
            "></span>
        @endfor
    </div>

    <div class="lw-stage">
        <p class="lw-hooray">🎉 {{ __('site.onboarding.launched.hooray') }}</p>
        <h1 class="lw-title">{{ __('site.onboarding.launched.title', ['name' => $tenant->name]) }}</h1>
        <p class="lw-lead">{{ __('site.onboarding.launched.lead') }}</p>

        <div class="lw-url-card">
            <span class="label">{{ __('site.onboarding.launched.url_label') }}</span>
            <span class="url">{{ $storefrontUrl }}</span>
        </div>

        <div class="lw-actions">
            <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-big">
                {{ __('site.onboarding.launched.visit') }} ↗
            </a>
            <a href="/store" class="btn btn-ghost btn-big">{{ __('site.onboarding.launched.admin') }} →</a>
        </div>
    </div>
@endsection
