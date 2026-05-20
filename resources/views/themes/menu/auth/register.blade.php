@php
    $title = __('site.common.create_account');
    $csSignup = $store->signupFieldsConfig();
    $reqMarker = fn (bool $req) => $req ? '' : ' <span style="color: var(--ink-soft); font-family: \'Playfair Display\', Georgia, serif; font-style: italic;">(' . __('site.auth.optional') . ')</span>';
@endphp
@extends('themes.menu.layout')

@section('content')
    <style>
        .auth-page { max-width: 540px; margin: 0 auto; padding: 4rem 1.5rem 6rem; }
        .auth-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-align: center;
            margin: 0 0 .5rem;
        }
        .auth-page h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-weight: 700;
            font-size: clamp(2rem, 4vw, 2.75rem);
            text-align: center;
            color: var(--ink);
            margin: 0 0 .5rem;
            line-height: 1.05;
        }
        .auth-page .lead {
            text-align: center;
            color: var(--ink-soft);
            font-style: italic;
            font-size: 1rem;
            margin: 0 0 2.5rem;
            font-family: 'Playfair Display', Georgia, serif;
        }
        .errors {
            border: 1px solid var(--rule);
            background: var(--paper-deep);
            color: var(--ink);
            padding: .875rem 1rem;
            margin: 0 0 1.5rem;
            font-size: 0.875rem;
        }
        .errors ul { margin: 0; padding-left: 1.125rem; }

        .field { margin: 0 0 1.25rem; }
        .field-label {
            display: block;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin: 0 0 .5rem;
        }
        .field-input {
            width: 100%;
            background: var(--paper);
            border: 1px solid var(--rule);
            padding: .875rem 1rem;
            font-family: inherit;
            font-size: 0.9375rem;
            color: var(--ink);
            transition: border-color .2s ease;
        }
        .field-input:focus { outline: none; border-color: var(--ink); }
        .field-hint { display: block; margin-top: .375rem; font-size: 0.75rem; color: var(--ink-soft); font-style: italic; font-family: 'Playfair Display', Georgia, serif; }

        .shipping-group { border-top: 1px dashed var(--rule); padding-top: 1.5rem; margin-top: 1.5rem; }
        .shipping-group .field-label { margin-bottom: .5rem; }
        .shipping-group .field-input { margin-bottom: .5rem; }
        .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: .5rem; }
        .marketing-field { margin-top: 1.25rem; }
        .marketing-label {
            display: flex; align-items: flex-start; gap: .625rem;
            cursor: pointer; font-size: 0.875rem; line-height: 1.5;
            color: var(--ink);
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
        }
        .marketing-label input { margin-top: .15rem; flex-shrink: 0; }

        .submit-btn {
            display: block;
            width: 100%;
            background: var(--ink);
            color: var(--paper);
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color .2s ease;
            font-family: inherit;
        }
        .submit-btn:hover { background: var(--primary-strong); }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: var(--ink-soft);
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
        }
        .auth-footer a { color: var(--ink); border-bottom: 1px solid currentColor; padding-bottom: 1px; }
    </style>

    <div class="auth-page">
        <p class="auth-eyebrow">{{ $tenant->name }}</p>
        <h1>{{ __('site.auth.register_title') }}</h1>
        <p class="lead">{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>

        @if ($errors->any())
            <div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="/account/register">
            @csrf
            <div class="field">
                <label class="field-label" for="name">{{ __('site.auth.full_name') }}</label>
                <input class="field-input" type="text" name="name" id="name" value="{{ old('name') }}" required autofocus>
            </div>
            <div class="field">
                <label class="field-label" for="email">{{ __('site.auth.email') }}</label>
                <input class="field-input" type="email" name="email" id="email" value="{{ old('email') }}" required>
            </div>
            <div class="field">
                <label class="field-label" for="password">{{ __('site.auth.password') }}</label>
                <input class="field-input" type="password" name="password" id="password" required>
                <span class="field-hint">{{ __('site.auth.password_hint') }}</span>
            </div>
            <div class="field">
                <label class="field-label" for="password_confirmation">{{ __('site.auth.password_confirm') }}</label>
                <input class="field-input" type="password" name="password_confirmation" id="password_confirmation" required>
            </div>

            @include('storefront.auth._signup_fields')

            <button type="submit" class="submit-btn">{{ __('site.auth.create_account_btn') }}</button>
        </form>

        <div class="auth-footer">
            {{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }}</a>
        </div>
    </div>
@endsection
