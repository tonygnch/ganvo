@php $title = __('site.common.sign_in'); @endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .auth-page { max-width: 440px; margin: 0 auto; padding: 4rem 1.5rem 6rem; }
        .auth-card {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px -10px rgba(15, 23, 42, .08);
        }
        .auth-card h1 {
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 .5rem;
        }
        .auth-card .lead {
            color: var(--text-muted);
            font-size: 0.9375rem;
            margin: 0 0 2rem;
        }
        .errors {
            border: 1px solid var(--hair);
            background: var(--surface-2);
            border-radius: 8px;
            padding: .75rem 1rem;
            margin: 0 0 1.25rem;
            font-size: 0.875rem;
            color: var(--text);
        }
        .errors ul { margin: 0; padding-left: 1.125rem; }

        .field { margin: 0 0 1rem; }
        .field-label {
            display: block;
            font-family: var(--mono);
            font-size: 0.6875rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin: 0 0 .375rem;
        }
        .field-input {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--hair);
            border-radius: 8px;
            padding: .75rem .875rem;
            font-size: 0.9375rem;
            color: var(--text);
            font-family: inherit;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .field-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-soft);
        }

        .submit-btn {
            display: block;
            width: 100%;
            background: var(--text);
            color: var(--bg);
            border: 0;
            border-radius: 10px;
            padding: .9375rem 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .15s ease;
            font-family: inherit;
        }
        .submit-btn:hover { background: var(--primary); }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .auth-footer a { color: var(--primary); font-weight: 600; }
        .auth-footer a:hover { text-decoration: underline; }
    </style>

    <div class="auth-page">
        <div class="auth-card">
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
    </div>
@endsection
