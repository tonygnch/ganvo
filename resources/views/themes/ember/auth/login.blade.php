@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.ember.layout')

@section('content')
    <style>
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; }

        .auth .card { position: relative; z-index: 2; background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px; width: min(440px, 100%); padding: 46px; text-align: center; box-shadow: 8px 8px 0 var(--soft2); }
        /* perforated receipt edge */
        .auth .card::before { content: ""; position: absolute; left: 30px; right: 30px; top: 96px; border-top: 2px dashed var(--line); }
        /* tenant kicker — hand-stamped chip */
        .auth .card .k { display: inline-block; margin-bottom: 8px; border: 1.5px solid var(--accent); border-radius: 2px; padding: 4px 11px; transform: rotate(-1.6deg); -webkit-mask-image: var(--stamp); mask-image: var(--stamp); }
        .auth .card h1 { font-family: var(--display); font-weight: 700; font-size: clamp(32px, 4vw, 46px); margin-bottom: 6px; }
        .auth .card h1 em { font-style: italic; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14px; margin-bottom: 26px; padding-bottom: 18px; }

        .auth .errors { text-align: left; border: 1.5px solid #b91c1c; background: color-mix(in srgb, #b91c1c 7%, var(--card)); color: var(--ink); border-radius: 2px; padding: 12px 15px; margin-bottom: 20px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field label { display: block; font-family: var(--mono); font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field input { width: 100%; border: 1.5px solid var(--line); border-radius: 2px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .auth .field input:focus { outline: none; border-color: var(--ink); }

        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); }
        .auth .alt a { color: var(--accent); font-weight: 600; }
    </style>

    <main>
        <div class="wrap">
            <section class="auth reveal">
                <div class="card">
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
                        <button type="submit" class="btn block accent">{{ __('site.auth.sign_in_btn') }}</button>
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
