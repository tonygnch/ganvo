@php $title = __('site.common.sign_in'); @endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .auth-page { max-width: 460px; margin: 0 auto; padding: 5rem 1.5rem 6rem; }
        .auth-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--text-muted);
            text-align: center;
            margin: 0 0 .75rem;
        }
        .auth-page h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 400;
            font-size: clamp(2.25rem, 4vw, 2.875rem);
            line-height: 1.05;
            text-align: center;
            margin: 0 0 .75rem;
            color: var(--text);
        }
        .auth-page .lead {
            text-align: center;
            color: var(--text-muted);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.125rem;
            margin: 0 0 3rem;
        }
        .errors {
            border: 1px solid var(--hair);
            color: var(--text);
            padding: .875rem 1rem;
            margin: 0 0 1.5rem;
            font-size: 0.875rem;
        }
        .errors ul { margin: 0; padding-left: 1.125rem; }

        .field { margin-bottom: 1.5rem; }
        .field-label {
            display: block;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .5rem;
        }
        .field-input {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--text);
            background: transparent;
            padding: .5rem 0;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.125rem;
            color: var(--text);
            transition: border-color .2s ease;
        }
        .field-input:focus { outline: none; border-color: var(--primary); }

        .submit-btn {
            display: block;
            width: 100%;
            background: var(--text);
            color: white;
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 2.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color .2s ease;
            font-family: inherit;
        }
        .submit-btn:hover { background: var(--primary); }

        .auth-footer {
            margin-top: 2rem;
            text-align: center;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1rem;
            color: var(--text-muted);
        }
        .auth-footer a { color: var(--text); border-bottom: 1px solid currentColor; padding-bottom: 1px; }
        .auth-footer a:hover { color: var(--primary); border-color: var(--primary); }
    </style>

    <div class="auth-page">
        <p class="auth-eyebrow">{{ $tenant->name }}</p>
        <h1>{{ __('site.auth.login_title') }}</h1>
        <p class="lead">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
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
