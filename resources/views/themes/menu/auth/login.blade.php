@php $title = __('site.common.sign_in'); @endphp
@extends('themes.menu.layout')

@section('content')
    <style>
        .auth-page { max-width: 460px; margin: 0 auto; padding: 4rem 1.5rem 6rem; }
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
        .ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            color: var(--ink-soft);
            margin: 0 0 2rem;
        }
        .ornament::before, .ornament::after { content: ""; height: 1px; background: var(--rule); width: 60px; }
        .ornament .dot { font-size: 0.5rem; }

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
        <h1>{{ __('site.auth.login_title') }}</h1>
        <p class="lead">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>
        <div class="ornament" aria-hidden="true"><span class="dot">●</span></div>

        @if ($errors->any())
            <div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="/account/login">
            @csrf
            <div class="field">
                <label class="field-label" for="email">{{ __('site.auth.email') }}</label>
                <input class="field-input" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label class="field-label" for="password">{{ __('site.auth.password') }}</label>
                <input class="field-input" type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="submit-btn">{{ __('site.auth.sign_in_btn') }}</button>
        </form>

        @if ($store->allow_registration)
            <div class="auth-footer">
                {{ __('site.auth.new_here') }} <a href="/account/register">{{ __('site.auth.create_account_link') }}</a>
            </div>
        @endif
    </div>
@endsection
