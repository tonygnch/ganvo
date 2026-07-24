{{--
 | Timber — sign in. The yard office counter: one framed panel of paper
 | stock ruled like a cutting list, a grading stamp inked on the corner,
 | end-grain rings settling in the daylight behind it.
--}}
@php
    $title = __('site.common.sign_in');
@endphp
@extends('themes.timber.layout')

@section('content')
    <style>
        /* ===== Sign in — a single counter panel centred on the yard floor. ===== */
        .auth { display: grid; place-items: center; min-height: 82vh; padding: 50px 20px; position: relative; overflow: hidden; }

        /* end-grain accents — sawn-log rings drifting on an easy clock */
        .auth .ring.a1 { width: 170px; height: 170px; top: 8%; left: 8%; opacity: .55; animation: ringdrift 7s ease-in-out infinite; }
        .auth .ring.a2 { width: 96px; height: 96px; bottom: 12%; right: 12%; opacity: .4; animation: ringdrift 6s ease-in-out infinite .5s; }
        @keyframes ringdrift { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @media (prefers-reduced-motion: reduce) { .auth .ring { animation: none !important; } }

        /* the panel — planed surface, hard walnut shadow, square shoulders */
        .auth .card { position: relative; z-index: 2; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; width: min(440px, 100%); padding: 42px 40px; text-align: center; box-shadow: 0 2px 0 0 var(--line); }
        /* grading stamp, inked over the top edge of the panel */
        .auth .card .gstamp { position: absolute; top: -13px; right: 20px; background: var(--surface); }

        /* head — ruled like a cutting list, ticks hanging off the rule */
        .auth .head { border-bottom: 2px solid var(--txt); padding-bottom: 16px; margin-bottom: 24px; }
        .auth .card .k { display: block; margin-bottom: 8px; }
        .auth .card h1 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; line-height: 1; font-size: clamp(34px, 4.4vw, 48px); margin-bottom: 8px; }
        .auth .card h1 em { font-style: normal; color: var(--accent-deep); }
        .auth .card .lede { color: var(--muted); font-size: 14px; }
        .auth .head + .rule-ticks { margin: -24px 0 20px; }

        /* rejected slip — the red-pencil note on the order */
        .auth .errors { text-align: left; border: 1px solid #b91c1c; background: color-mix(in srgb, #b91c1c 7%, var(--surface)); color: var(--txt); border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; font-family: var(--mono); font-size: 12px; line-height: 1.55; box-shadow: 0 2px 0 0 color-mix(in srgb, #b91c1c 30%, var(--line)); }
        .auth .errors ul { margin: 0; padding-left: 0; list-style: none; }
        .auth .errors li { display: flex; gap: 8px; }
        .auth .errors li::before { content: "▮"; color: #b91c1c; }

        /* spec fields — mono labels over planed input slots */
        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field label { display: block; font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field input { width: 100%; border: 1px solid var(--line2); border-radius: 6px; background: var(--bg); padding: 13px 15px; font-family: var(--body); font-size: 15px; color: var(--txt); transition: border-color .2s ease; }
        .auth .field input::placeholder { color: var(--faint); }
        .auth .field input:focus { outline: none; border-color: var(--accent); }
        .auth .card .btn.block { margin-top: 6px; }

        .auth .alt { margin-top: 22px; font-family: var(--mono); font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .auth .alt a { color: var(--accent-deep); font-weight: 600; border-bottom: 1px solid transparent; }
        .auth .alt a:hover { border-color: currentColor; }

        @media (max-width: 540px) {
            .auth { padding: 34px 4px; }
            .auth .card { padding: 32px 24px; }
        }
    </style>

    <main>
        <div class="wrap">
            <section class="auth reveal">
                @if ($theme->on('grain_rings'))
                    <div class="ring a1" aria-hidden="true"></div>
                    <div class="ring a2" aria-hidden="true"></div>
                @endif

                <div class="card">
                    @if ($theme->on('grade_stamp'))
                        <span class="stamp-tag gstamp" aria-hidden="true">{{ $theme->label('grade_stamp') }}</span>
                    @endif

                    <div class="head">
                        <span class="kicker k">{{ $tenant->name }}</span>
                        <h1>{{ __('site.auth.login_title') }}</h1>
                        <p class="lede">{{ __('site.auth.login_lead', ['tenant' => $tenant->name]) }}</p>
                    </div>
                    @if ($theme->on('ruler'))<div class="rule-ticks" aria-hidden="true"></div>@endif

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
