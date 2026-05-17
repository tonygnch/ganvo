@php
    $title = __('site.common.create_account');
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .auth-page {
            max-width: 420px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        .auth-card {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 2rem;
        }
        .auth-card h1 {
            margin: 0 0 .25rem;
            font-size: 1.625rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .auth-card .lead {
            color: var(--text-muted, #57534e);
            margin: 0 0 1.5rem;
            font-size: 0.9375rem;
        }
        .field { margin-bottom: 1rem; }
        label.field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: .375rem;
        }
        input.field-input {
            width: 100%;
            padding: .75rem .875rem;
            border: 1px solid var(--border, #e7e5e4);
            border-radius: .625rem;
            background: var(--surface, white);
            font: inherit;
            font-size: 0.9375rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        input.field-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 18%, transparent);
        }
        .field-hint {
            display: block;
            margin-top: .25rem;
            font-size: 0.75rem;
            color: var(--text-soft, #a8a29e);
        }
        .errors {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: .75rem 1rem;
            border-radius: .625rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
        }
        .errors ul { margin: 0; padding-left: 1.25rem; }
        .submit-btn {
            width: 100%;
            background: var(--primary);
            color: white;
            border: 0;
            padding: .875rem;
            border-radius: .625rem;
            font-weight: 700;
            font-size: 0.9375rem;
            cursor: pointer;
            margin-top: .5rem;
            transition: background-color .2s ease, transform .12s ease;
        }
        .submit-btn:hover { background: var(--primary-strong, var(--primary)); transform: translateY(-1px); }
        .auth-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: var(--text-muted, #57534e);
        }
        .auth-footer a { color: var(--primary); font-weight: 600; }
        .auth-footer a:hover { text-decoration: underline; }
    </style>

    <div class="auth-page">
        <div class="auth-card">
            <h1>{{ __('site.auth.register_title') }}</h1>
            <p class="lead">{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
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
                <button type="submit" class="submit-btn">{{ __('site.auth.create_account_btn') }}</button>
            </form>

            <div class="auth-footer">
                {{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }}</a>
            </div>
        </div>
    </div>
@endsection
