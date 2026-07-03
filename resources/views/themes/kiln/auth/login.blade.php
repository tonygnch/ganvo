@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.kiln.layout')

@section('content')
    <style>
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; overflow: hidden; }

        .auth .card { position: relative; z-index: 2; background: var(--card); border: 1px solid var(--line); border-radius: 2px; width: min(440px, 100%); padding: 46px; text-align: center; }
        .auth .card .rings-mark { margin: 0 auto 18px; width: 46px; height: 46px; }
        .auth .card .k { display: block; margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--serif); font-size: clamp(32px, 4vw, 46px); margin-bottom: 6px; font-weight: 400; letter-spacing: -.01em; }
        .auth .card h1 em { font-style: italic; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14px; margin-bottom: 26px; }

        .auth .errors { text-align: left; border: 1px solid #9c5a3e; background: color-mix(in srgb, #9c5a3e 8%, var(--card)); color: var(--ink); border-radius: 2px; padding: 12px 15px; margin-bottom: 20px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field label { display: block; font-family: var(--display); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field input { width: 100%; border: 1px solid var(--line); border-radius: 2px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .auth .field input:focus { outline: none; border-color: var(--ink); }

        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); }
        .auth .alt a { color: var(--ink); border-bottom: 1px solid var(--accent); }
    </style>

    <main>
        <div class="wrap">
            <section class="auth reveal">
                <div class="card">
                    <div class="rings-mark" aria-hidden="true"></div>
                    <span class="kicker k">{{ $tenant->name }}</span>
                    <h1>{{ __('site.auth.login_title') }}</h1>
                    <p class="lede">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>

                    @if ($errors->any())
                        <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <form method="post" action="/account/login">
                        @csrf
                        <div class="field">
                            <label for="email">{{ __('site.auth.email') }}</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                        </div>
                        <div class="field">
                            <label for="password">{{ __('site.auth.password') }}</label>
                            <input type="password" name="password" id="password" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn block">{{ __('site.auth.sign_in_btn') }}</button>
                    </form>
                </div>

                @if ($store->allow_registration)
                    <div class="alt">
                        {{ __('site.auth.new_here') }} <a href="/account/register">{{ __('site.auth.create_account_link') }} →</a>
                    </div>
                @endif
            </section>
        </div>
    </main>
@endsection
