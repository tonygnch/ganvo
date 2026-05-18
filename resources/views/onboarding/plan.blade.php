@php $title = __('site.onboarding.plan.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin: 2rem 0;
        }
        .plan-card {
            border: 2px solid var(--hair);
            border-radius: 1rem;
            padding: 1.75rem 1.5rem;
            cursor: pointer;
            transition: border-color .15s ease, transform .12s ease, box-shadow .15s ease;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .plan-card:hover { border-color: var(--text-muted); transform: translateY(-2px); }
        .plan-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        /* Selection styling driven purely by :has(:checked) so it actually
           tracks clicks. The earlier .selected class was applied server-side
           and never removed on the client. */
        .plan-card:has(input[type="radio"]:checked) {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 18%, transparent);
        }
        .plan-card .featured-badge {
            position: absolute;
            top: -.625rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text);
            color: white;
            padding: .25rem .75rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .plan-name {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .5rem;
        }
        .plan-price {
            font-size: 1.625rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text);
            margin: 0 0 .25rem;
        }
        .plan-tagline {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin: 0 0 1.25rem;
            min-height: 2.5em;
        }
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
            font-size: 0.875rem;
            color: var(--text);
        }
        .plan-features li {
            padding: .375rem 0;
            padding-left: 1.25rem;
            position: relative;
        }
        .plan-features li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: 700;
        }
        @media (max-width: 760px) {
            .plan-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="panel wide">
        <p class="panel-eyebrow">{{ __('site.onboarding.plan.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.plan.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.plan.lead') }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/plan">
            @csrf

            <div class="plan-grid">
                @foreach ($plans as $key => $plan)
                    @php
                        $isFeatured = $key === 'pro';
                        $isSelected = old('subscription_plan', $tenant->subscription_plan ?? 'starter') === $key;
                    @endphp
                    <label class="plan-card">
                        @if ($isFeatured)
                            <span class="featured-badge">{{ __('site.onboarding.plan.popular') }}</span>
                        @endif
                        <input type="radio" name="subscription_plan" value="{{ $key }}" @if($isSelected) checked @endif required>
                        <p class="plan-name">{{ __('site.onboarding.plan.names.' . $key) }}</p>
                        <p class="plan-price">{{ $plan['price_label'] }}</p>
                        <p class="plan-tagline">{{ $plan['tagline'] }}</p>
                        <ul class="plan-features">
                            @foreach ($plan['features'] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </label>
                @endforeach
            </div>

            <div class="actions">
                <a href="/onboarding/business" class="btn btn-ghost">← {{ __('site.onboarding.plan.back') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('site.onboarding.plan.cta') }} →</button>
            </div>
        </form>
    </div>
@endsection
