@php $title = __('site.onboarding.products.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <style>
        .pp-layout {
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            gap: 1.75rem;
            max-width: 1100px;
            width: 100%;
        }
        .pp-card {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 1.25rem;
            box-shadow: 0 30px 60px -30px rgba(0,0,0,.08);
            padding: 2.25rem;
            align-self: start;
        }
        .pp-side {
            position: sticky;
            top: 1rem;
            align-self: start;
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 1.25rem;
            padding: 1.5rem;
        }
        .pp-side h3 {
            margin: 0 0 1rem;
            font-size: 0.75rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }
        .pp-empty {
            color: var(--text-soft);
            font-size: 0.875rem;
            padding: 1rem 0;
            text-align: center;
        }
        .pp-list { display: flex; flex-direction: column; gap: .75rem; }
        .pp-item {
            display: grid;
            grid-template-columns: 56px 1fr auto;
            gap: .75rem;
            align-items: center;
            padding: .625rem;
            border: 1px solid var(--hair);
            border-radius: .625rem;
            background: var(--surface);
        }
        .pp-thumb {
            width: 56px; height: 56px;
            background: var(--muted);
            border-radius: .5rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft);
            font-size: 0.625rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .pp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pp-item .name { font-weight: 600; font-size: 0.9375rem; line-height: 1.3; }
        .pp-item .meta { font-size: 0.75rem; color: var(--text-muted); }
        .pp-item .price { font-weight: 700; font-size: 0.9375rem; }

        .pp-flash {
            background: color-mix(in srgb, var(--primary) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--primary) 30%, transparent);
            color: var(--primary-strong);
            padding: .75rem 1rem;
            border-radius: .625rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .pp-actions {
            display: flex;
            gap: .75rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .pp-actions .btn { flex: 1; min-width: 0; }

        @media (max-width: 980px) {
            .pp-layout { grid-template-columns: 1fr; }
            .pp-side { position: static; }
        }
    </style>

    <div class="pp-layout">
        <div class="pp-card">
            <p class="panel-eyebrow">{{ __('site.onboarding.products.eyebrow') }}</p>
            <h1>{{ __('site.onboarding.products.title') }}</h1>
            <p class="lead">{{ __('site.onboarding.products.lead') }}</p>

            @if (session('flash'))
                <div class="pp-flash">✓ {{ session('flash') }}</div>
            @endif

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="/onboarding/products" enctype="multipart/form-data" id="pp-form">
                @csrf

                <div class="field">
                    <label class="lbl" for="name">{{ __('site.onboarding.products.name') }}</label>
                    <input class="input" type="text" name="name" id="name" value="{{ old('name') }}" placeholder="{{ __('site.onboarding.products.name_ph') }}" autofocus>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="lbl" for="price">{{ __('site.onboarding.products.price') }}</label>
                        <input class="input" type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price') }}" placeholder="19.99">
                        <p class="help">{{ __('site.onboarding.products.price_help', ['currency' => $store->currency ?? 'USD']) }}</p>
                    </div>
                    <div class="field">
                        <label class="lbl" for="image">{{ __('site.onboarding.products.image') }}</label>
                        <input class="input" type="file" name="image" id="image" accept="image/*">
                    </div>
                </div>

                <div class="field">
                    <label class="lbl" for="description">{{ __('site.onboarding.products.description') }}</label>
                    <textarea class="input" name="description" id="description" rows="3" placeholder="{{ __('site.onboarding.products.description_ph') }}">{{ old('description') }}</textarea>
                </div>

                <input type="hidden" name="action" id="pp-action" value="continue">
                <div class="pp-actions">
                    <button type="submit" class="btn btn-ghost" name="action" value="another">+ {{ __('site.onboarding.products.cta_another') }}</button>
                    <button type="submit" class="btn btn-primary" name="action" value="continue">{{ __('site.onboarding.products.cta_continue') }} →</button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                @if ($products->isEmpty())
                    <form method="post" action="/onboarding/products" style="display:inline;">
                        @csrf
                        <input type="hidden" name="action" value="skip">
                        <button type="submit" class="btn btn-ghost" style="padding: .375rem 1rem; font-size: 0.8125rem;">{{ __('site.onboarding.products.cta_skip') }}</button>
                    </form>
                @endif
            </div>
        </div>

        <aside class="pp-side">
            <h3>{{ trans_choice('site.onboarding.products.added_count', $products->count(), ['count' => $products->count()]) }}</h3>
            @if ($products->isEmpty())
                <div class="pp-empty">{{ __('site.onboarding.products.none_yet') }}</div>
            @else
                <div class="pp-list">
                    @foreach ($products as $p)
                        <div class="pp-item">
                            <div class="pp-thumb">
                                @if ($p->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($p->image_path) }}" alt="">
                                @else
                                    {{ __('site.onboarding.products.no_image') }}
                                @endif
                            </div>
                            <div>
                                <div class="name">{{ $p->name }}</div>
                                <div class="meta">{{ \App\Services\Money::format($p->price_cents, $store->currency ?? 'USD') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
@endsection
