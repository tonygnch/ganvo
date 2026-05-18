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

        .lp-cd-toggle {
            border: 1px solid var(--hair);
            border-radius: .75rem;
            padding: 1rem 1.25rem;
            margin: 1rem 0;
            background: var(--surface);
        }
        .lp-cd-toggle summary {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            list-style: none;
            user-select: none;
        }
        .lp-cd-toggle summary::-webkit-details-marker { display: none; }
        .lp-cd-toggle summary::marker { display: none; content: none; }
        .lp-cd-toggle .lp-cd-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            border-radius: 50%;
            background: var(--muted);
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1;
            transition: transform .15s ease, background-color .15s ease;
        }
        .lp-cd-toggle[open] .lp-cd-icon {
            transform: rotate(45deg);
            background: var(--primary);
            color: white;
        }
        .lp-cd-body {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--hair);
        }
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

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/launch">
            @csrf

            <details class="lp-cd-toggle" @if($store->custom_domain) open @endif>
                <summary>
                    <span class="lp-cd-icon">+</span>
                    {{ __('site.onboarding.launch.custom_domain_toggle') }}
                </summary>
                <div class="lp-cd-body">
                    <p class="help" style="margin: 0 0 .75rem;">{{ __('site.onboarding.launch.custom_domain_help') }}</p>
                    <input class="input" type="text" name="custom_domain"
                           value="{{ old('custom_domain', $store->custom_domain) }}"
                           placeholder="shop.acmecorp.com"
                           pattern="[a-z0-9][a-z0-9.\-]+[a-z0-9]"
                           maxlength="255">
                    <p class="help" style="margin-top: .5rem;">{{ __('site.onboarding.launch.custom_domain_after') }}</p>
                </div>
            </details>

            <div class="lp-launch-row">
                <a href="/onboarding/products" class="btn btn-ghost">← {{ __('site.onboarding.launch.back') }}</a>
                <button type="submit" class="btn btn-go">🚀 {{ __('site.onboarding.launch.cta') }}</button>
            </div>
        </form>
    </div>
@endsection
