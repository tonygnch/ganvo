@php
    $title = __('site.common.create_account');
    $csSignup = $store->signupFieldsConfig();
    $reqMarker = fn (bool $req) => $req ? '' : ' <span style="color: var(--text-soft); font-family: var(--mono); font-size: 0.625rem;">(' . __('site.auth.optional') . ')</span>';
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .auth-page { max-width: 520px; margin: 0 auto; padding: 4rem 1.5rem 6rem; }
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
        .field-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
        .field-hint { display: block; margin-top: .375rem; font-family: var(--mono); font-size: 0.6875rem; color: var(--text-soft); }

        .shipping-group { border-top: 1px solid var(--hair); padding-top: 1.25rem; margin-top: 1.25rem; }
        .shipping-group .field-input { margin-bottom: .5rem; }
        .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: .5rem; }
        .marketing-field { margin-top: 1.25rem; }
        .marketing-label {
            display: flex; align-items: flex-start; gap: .625rem;
            cursor: pointer; font-size: 0.875rem; line-height: 1.5;
            color: var(--text);
        }
        .marketing-label input { margin-top: .15rem; flex-shrink: 0; }

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
            font-family: inherit;
            transition: background-color .15s ease;
        }
        .submit-btn:hover { background: var(--primary); }

        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .auth-footer a { color: var(--primary); font-weight: 600; }
    </style>

    <div class="auth-page">
        <div class="auth-card">
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
    </div>
@endsection
