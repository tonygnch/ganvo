@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.wick.layout')

@section('content')
    <style>
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; overflow: hidden; }
        .auth .leafdot { position: absolute; border-radius: 50%; }
        .auth .l1 { width: 120px; height: 120px; background: var(--jar2); top: 8%; left: 10%; opacity: .5; animation: floaty 7s ease-in-out infinite; }
        .auth .l2 { width: 90px; height: 90px; background: var(--jar); bottom: 12%; right: 14%; opacity: .5; animation: floaty 6s ease-in-out infinite .5s; }
        @keyframes floaty { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
        @media (prefers-reduced-motion: reduce) { .auth .leafdot { animation: none !important; } }

        .auth .card { position: relative; z-index: 2; background: var(--card); border: 1px solid var(--line); border-radius: 14px; width: min(440px, 100%); padding: 46px; text-align: center; box-shadow: 0 40px 80px -40px rgba(0, 0, 0, .65); }
        .auth .card .tape { width: 120px; height: 28px; margin-left: -60px; }
        .auth .card .k { display: block; margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--display); font-size: clamp(32px, 4vw, 46px); margin-bottom: 6px; font-weight: 800; letter-spacing: -.02em; }
        .auth .card h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14px; margin-bottom: 26px; }

        .auth .errors { text-align: left; border: 1px solid #b91c1c; background: color-mix(in srgb, #b91c1c 7%, var(--card)); color: var(--ink); border-radius: 10px; padding: 12px 15px; margin-bottom: 20px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field label { display: block; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field input { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .auth .field input:focus { outline: none; border-color: var(--accent); }

        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); }
        .auth .alt a { color: var(--accent); font-weight: 600; }
    </style>

    <main>
        <div class="wrap">
            <section class="auth reveal">
                <div class="leafdot l1" aria-hidden="true"></div>
                <div class="leafdot l2" aria-hidden="true"></div>

                <div class="card">
                    <div class="tape" aria-hidden="true"></div>
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
