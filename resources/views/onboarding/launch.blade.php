@php $title = __('site.onboarding.launch.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        .lp-card {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 1.25rem;
            box-shadow: 0 30px 60px -30px rgba(0,0,0,.08);
            padding: 2.5rem;
            max-width: 700px;
            width: 100%;
        }
        .lp-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 2rem 0;
        }
        .lp-cell {
            border: 1px solid var(--hair);
            border-radius: .75rem;
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }
        .lp-cell .label {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .lp-cell .value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text);
        }
        .lp-cell .edit {
            position: absolute;
            top: .625rem; right: .75rem;
            font-size: 0.6875rem;
            color: var(--text-soft);
        }
        .lp-cell {
            position: relative;
        }
        .lp-cell:hover .edit { color: var(--text-muted); }
        .lp-cell:hover { border-color: var(--text-muted); }
        .lp-swatches {
            display: flex;
            gap: .375rem;
            align-items: center;
        }
        .lp-swatch {
            width: 22px; height: 22px;
            border-radius: 6px;
            border: 1px solid var(--hair);
        }
        .lp-url {
            background: var(--muted);
            border: 1px solid var(--hair);
            border-radius: .625rem;
            padding: .75rem 1rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.875rem;
            color: var(--text);
            margin: 1rem 0;
            word-break: break-all;
        }
        .lp-launch-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn-go {
            background: var(--primary);
            color: white;
            font-size: 1rem;
            padding: 1rem 2rem;
        }
        .btn-go:hover { background: var(--primary-strong); transform: translateY(-1px); }
    </style>

    <div class="lp-card">
        <p class="panel-eyebrow">{{ __('site.onboarding.launch.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.launch.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.launch.lead') }}</p>

        <div class="lp-summary">
            <div class="lp-cell">
                <a href="/onboarding/business" class="edit">{{ __('site.onboarding.launch.edit') }} →</a>
                <span class="label">{{ __('site.onboarding.steps.business') }}</span>
                <span class="value">{{ $tenant->name }}</span>
            </div>
            <div class="lp-cell">
                <a href="/onboarding/plan" class="edit">{{ __('site.onboarding.launch.edit') }} →</a>
                <span class="label">{{ __('site.onboarding.steps.plan') }}</span>
                <span class="value">{{ __('site.onboarding.plan.names.' . ($tenant->subscription_plan ?? 'starter')) }}</span>
            </div>
            <div class="lp-cell">
                <a href="/onboarding/theme" class="edit">{{ __('site.onboarding.launch.edit') }} →</a>
                <span class="label">{{ __('site.onboarding.steps.theme') }}</span>
                <span class="value">{{ ucfirst($store->theme) }}</span>
            </div>
            <div class="lp-cell">
                <a href="/onboarding/customize" class="edit">{{ __('site.onboarding.launch.edit') }} →</a>
                <span class="label">{{ __('site.onboarding.steps.customize') }}</span>
                <span class="value lp-swatches">
                    <span class="lp-swatch" style="background: {{ $store->primary_color }}"></span>
                    <span class="lp-swatch" style="background: {{ $store->secondary_color }}"></span>
                    <span style="font-weight: 500; font-size: 0.8125rem; color: var(--text-muted); margin-left: .375rem;">{{ $store->font_family }}</span>
                </span>
            </div>
            <div class="lp-cell" style="grid-column: 1 / -1;">
                <a href="/onboarding/products" class="edit">{{ __('site.onboarding.launch.edit') }} →</a>
                <span class="label">{{ __('site.onboarding.steps.products') }}</span>
                <span class="value">
                    @if ($productCount > 0)
                        {{ trans_choice('site.onboarding.launch.product_count', $productCount, ['count' => $productCount]) }}
                    @else
                        <span style="color: var(--text-muted);">{{ __('site.onboarding.launch.no_products') }}</span>
                    @endif
                </span>
            </div>
        </div>

        <p class="lead" style="margin-bottom: .5rem;">{{ __('site.onboarding.launch.url_intro') }}</p>
        <div class="lp-url">{{ $storefrontUrl }}</div>

        <form method="post" action="/onboarding/launch">
            @csrf
            <div class="lp-launch-row">
                <a href="/onboarding/products" class="btn btn-ghost">← {{ __('site.onboarding.launch.back') }}</a>
                <button type="submit" class="btn btn-go">🚀 {{ __('site.onboarding.launch.cta') }}</button>
            </div>
        </form>
    </div>
@endsection
