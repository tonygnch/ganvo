{{--
 | Sankevi — sign in. One sheet on the counter, the forest standing behind it:
 | the panel is cut at two corners and the seal hangs off its top edge.
--}}
@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    <style>
        .auth { position: relative; display: grid; place-items: center; min-height: 80vh; padding: 60px 0; }
        /* a soft moss glow behind the sheet — the only light in the yard */
        .auth::before { content: ""; position: absolute; width: min(720px, 90%); aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, color-mix(in srgb, var(--moss) 42%, transparent), transparent 68%); pointer-events: none; }
        .auth .card { position: relative; z-index: 2; width: min(452px, 100%); background: var(--surface); border: 1px solid var(--line); padding: 46px 44px; }
        .auth .card .seal { position: absolute; top: -26px; right: 26px; width: 52px; height: 52px; object-fit: contain; }

        .auth .head { padding-bottom: 22px; margin-bottom: 26px; border-bottom: 1px solid var(--line2); }
        .auth .card .k { display: block; margin-bottom: 14px; }
        .auth .card h1 { font-family: var(--display); font-weight: 500; font-size: clamp(27px, 3.4vw, 37px); line-height: 1.02; letter-spacing: 0; margin-bottom: 10px; }
        .auth .card h1 em { font-style: normal; font-weight: 600; color: var(--accent); }
        .auth .card .lede { color: var(--muted); font-size: 14.5px; }

        .auth .errors { border: 1px solid #b4614a; border-left-width: 3px; background: color-mix(in srgb, #b4614a 12%, var(--surface)); padding: 13px 16px; margin-bottom: 22px; font-size: 13px; line-height: 1.55; }
        .auth .errors ul { list-style: none; }
        .auth .errors li { display: flex; gap: 9px; }
        .auth .errors li::before { content: "—"; color: #d08a70; }

        .auth .field { margin-bottom: 18px; }
        .auth .field label { display: block; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 9px; }
        .auth .field input { width: 100%; background: var(--bg); border: 1px solid var(--line2); padding: 14px 15px; font-family: var(--body); font-size: 15px; color: var(--txt); transition: border-color .25s ease; }
        .auth .field input::placeholder { color: var(--faint); }
        .auth .field input:focus { outline: none; border-color: var(--accent); }
        .auth .card .btn.block { margin-top: 8px; }

        .auth .alt { margin-top: 26px; text-align: center; font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .auth .alt a { color: var(--accent); border-bottom: 1px solid transparent; }
        .auth .alt a:hover { border-color: currentColor; }

        @media (max-width: 560px) {
            .auth { padding: 40px 0; }
            .auth .card { padding: 34px 24px; }
        }
    </style>

    <main>
        <div class="wrap">
            <section class="auth reveal">
                <div class="card cut cut-lg">
                    @if ($theme->on('brand_seal') && ($seal = $theme->image('seal_image')))
                        <img class="seal" src="{{ $seal }}" alt="" aria-hidden="true">
                    @endif

                    <div class="head">
                        <span class="kicker k">{{ $tenant->name }}</span>
                        <h1>{{ __('site.auth.login_title') }}</h1>
                        <p class="lede">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>
                    </div>

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
