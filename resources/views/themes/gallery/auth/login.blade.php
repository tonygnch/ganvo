@php $title = __('site.common.sign_in'); @endphp
@extends('themes.gallery.layout')

@section('content')
    <style>
        .auth-page { max-width: 480px; margin: 0 auto; padding: 5rem 2rem 6rem; }
        .auth-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 1rem;
        }
        .auth-page h1 {
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            margin: 0 0 .5rem;
            line-height: 1.05;
        }
        .auth-page .lead {
            color: var(--text-muted);
            font-size: 1rem;
            margin: 0 0 2.5rem;
        }
        .errors {
            border: 1px solid var(--hair);
            background: var(--muted);
            color: var(--text);
            padding: .875rem 1rem;
            margin: 0 0 1.5rem;
            font-size: 0.875rem;
        }
        .errors ul { margin: 0; padding-left: 1.125rem; }
        .field { margin-bottom: 1.5rem; }
        .field-label {
            display: block;
            font-size: 0.75rem;
            letter-spacing: 0.06em;
            font-weight: 500;
            color: var(--text);
            margin: 0 0 .5rem;
        }
        .field-input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--hair);
            padding: .875rem 1rem;
            font-size: 0.9375rem;
            color: var(--text);
            font-family: inherit;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .field-input:focus { outline: none; border-color: var(--text); }

        .submit-btn {
            display: block;
            width: 100%;
            background: var(--text);
            color: var(--bg);
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity .2s ease;
            font-family: inherit;
        }
        .submit-btn:hover { opacity: .85; }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .auth-footer a { color: var(--text); border-bottom: 1px solid var(--text); padding-bottom: 1px; }
        .auth-footer a:hover { color: var(--primary); border-color: var(--primary); }
    </style>

    <div class="auth-page">
        <p class="auth-eyebrow">{{ $tenant->name }}</p>
        <h1>{{ __('site.auth.login_title') }}</h1>
        <p class="lead">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>

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
