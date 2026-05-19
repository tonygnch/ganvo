@php $title = __('site.onboarding.plan.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        /* ---- Billing-period segmented control ---- */
        .billing-toggle {
            display: inline-flex;
            background: var(--muted);
            border-radius: 9999px;
            padding: 4px;
            margin: 1.5rem auto 0;
            position: relative;
        }
        .billing-toggle label {
            position: relative;
            padding: .5rem 1.25rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 9999px;
            transition: color .15s ease;
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            user-select: none;
        }
        .billing-toggle input { position: absolute; opacity: 0; pointer-events: none; }
        .billing-toggle label:has(input:checked) {
            background: var(--surface);
            color: var(--text);
            box-shadow: 0 2px 4px rgba(0,0,0,.06);
        }
        .billing-toggle .savings-pill {
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 1px 6px;
            background: color-mix(in srgb, var(--primary) 18%, transparent);
            color: var(--primary-strong);
            border-radius: 6px;
        }

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
            white-space: nowrap;
        }
        .plan-card .discount-badge {
            position: absolute;
            top: .875rem;
            right: .875rem;
            background: color-mix(in srgb, var(--primary) 14%, transparent);
            color: var(--primary-strong);
            padding: .25rem .5rem;
            border-radius: 6px;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.08em;
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

        /* Price block — base + striked + period suffix + savings */
        .plan-pricing { margin: 0 0 1.25rem; min-height: 4.5em; }
        .plan-price-row { display: flex; align-items: baseline; gap: .5rem; }
        .plan-price {
            font-size: 1.625rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text);
            margin: 0;
        }
        .plan-price-strike {
            font-size: 0.9375rem;
            color: var(--text-soft);
            text-decoration: line-through;
            text-decoration-thickness: 1.5px;
        }
        .plan-price-suffix {
            font-size: 0.8125rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .plan-price-aux {
            margin: .375rem 0 0;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .plan-price-aux.savings { color: var(--primary-strong); font-weight: 600; }

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

        /* ---- Pricing layers — only one shows per period via :has() on the form root ---- */
        .plan-card .for-monthly { display: block; }
        .plan-card .for-yearly  { display: none; }
        form:has(input[name="billing_period"][value="yearly"]:checked) .for-monthly { display: none; }
        form:has(input[name="billing_period"][value="yearly"]:checked) .for-yearly  { display: block; }
    </style>

    <div class="panel wide" style="text-align: center;">
        <p class="panel-eyebrow">{{ __('site.onboarding.plan.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.plan.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.plan.lead') }}</p>

        @if ($errors->any())
            <div class="errors" style="text-align: left;">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/plan" style="text-align: left;">
            @csrf

            @php
                // Detect a representative yearly-savings figure from the
                // costliest plan in the list, so the toggle pill can advertise
                // "save X%" honestly without picking a value out of the air.
                $maxSavingsPct = 0;
                foreach ($plans as $p) { $maxSavingsPct = max($maxSavingsPct, $p->yearlySavingsPercent()); }
                $currentPeriod = old('billing_period', $tenant->billing_period ?? 'monthly');
            @endphp

            <div style="text-align: center;">
                <div class="billing-toggle" role="tablist">
                    <label>
                        <input type="radio" name="billing_period" value="monthly" @if($currentPeriod === 'monthly') checked @endif>
                        {{ __('site.onboarding.plan.billing_monthly') }}
                    </label>
                    <label>
                        <input type="radio" name="billing_period" value="yearly" @if($currentPeriod === 'yearly') checked @endif>
                        {{ __('site.onboarding.plan.billing_yearly') }}
                        @if ($maxSavingsPct > 0)
                            <span class="savings-pill">−{{ $maxSavingsPct }}%</span>
                        @endif
                    </label>
                </div>
            </div>

            <div class="plan-grid">
                @foreach ($plans as $plan)
                    @php
                        $isSelected = old('subscription_plan', $tenant->subscription_plan ?? $plans->first()->slug) === $plan->slug;
                        $monthlyEff = $plan->effectivePriceCentsFor('monthly');
                        $yearlyEff  = $plan->effectivePriceCentsFor('yearly');
                        $hasDiscount = $plan->hasActiveDiscount();
                    @endphp
                    <label class="plan-card">
                        @if ($plan->is_popular)
                            <span class="featured-badge">{{ __('site.onboarding.plan.popular') }}</span>
                        @endif
                        @if ($hasDiscount)
                            <span class="discount-badge">
                                −{{ $plan->discount_percent }}%@if ($plan->discount_label) · {{ $plan->discount_label }} @endif
                            </span>
                        @endif

                        <input type="radio" name="subscription_plan" value="{{ $plan->slug }}" @if($isSelected) checked @endif required>

                        <p class="plan-name">{{ $plan->name }}</p>

                        <div class="plan-pricing">
                            {{-- Monthly view --}}
                            <div class="for-monthly">
                                @if ($plan->isFree())
                                    <p class="plan-price">{{ __('site.onboarding.plan.free') }}</p>
                                @else
                                    <div class="plan-price-row">
                                        <span class="plan-price">{{ \App\Services\Money::format($monthlyEff, $plan->currency) }}</span>
                                        @if ($hasDiscount && $monthlyEff !== $plan->price_monthly_cents)
                                            <span class="plan-price-strike">{{ \App\Services\Money::format($plan->price_monthly_cents, $plan->currency) }}</span>
                                        @endif
                                        <span class="plan-price-suffix">{{ __('site.onboarding.plan.per_month') }}</span>
                                    </div>
                                    @if ($hasDiscount && $plan->discount_ends_at)
                                        <p class="plan-price-aux">{{ __('site.onboarding.plan.promo_ends', ['date' => $plan->discount_ends_at->isoFormat('LL')]) }}</p>
                                    @endif
                                @endif
                            </div>

                            {{-- Yearly view --}}
                            <div class="for-yearly">
                                @if ($plan->isFree())
                                    <p class="plan-price">{{ __('site.onboarding.plan.free') }}</p>
                                @else
                                    <div class="plan-price-row">
                                        <span class="plan-price">{{ \App\Services\Money::format($yearlyEff, $plan->currency) }}</span>
                                        @if ($hasDiscount && $yearlyEff !== $plan->price_yearly_cents)
                                            <span class="plan-price-strike">{{ \App\Services\Money::format($plan->price_yearly_cents, $plan->currency) }}</span>
                                        @endif
                                        <span class="plan-price-suffix">{{ __('site.onboarding.plan.per_year') }}</span>
                                    </div>
                                    @if ($plan->yearlySavingsCents() > 0)
                                        <p class="plan-price-aux savings">{{ __('site.onboarding.plan.you_save', ['amount' => \App\Services\Money::format($plan->yearlySavingsCents(), $plan->currency)]) }}</p>
                                    @elseif ($hasDiscount && $plan->discount_ends_at)
                                        <p class="plan-price-aux">{{ __('site.onboarding.plan.promo_ends', ['date' => $plan->discount_ends_at->isoFormat('LL')]) }}</p>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if ($plan->tagline)
                            <p class="plan-tagline">{{ $plan->tagline }}</p>
                        @endif

                        <ul class="plan-features">
                            @foreach ((array) ($plan->features ?? []) as $feature)
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
