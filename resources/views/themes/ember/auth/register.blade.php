@php
    $title = __('site.common.create_account');
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

        /* errors */
        .auth .errors { text-align: left; border: 1.5px solid #b91c1c; background: color-mix(in srgb, #b91c1c 6%, var(--card)); color: var(--ink); border-radius: 2px; padding: 12px 16px; margin-bottom: 22px; font-size: 13px; }
        .auth .errors ul { margin: 0; padding-left: 18px; }

        /* field chrome — class names match the shared _signup_fields partial */
        .auth .field { text-align: left; margin-bottom: 16px; }
        .auth .field-label { display: block; font-family: var(--mono); font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .auth .field-input { width: 100%; border: 1.5px solid var(--line); border-radius: 2px; background: var(--bg); padding: 13px 15px; font-family: inherit; font-size: 15px; color: var(--ink); }
        .auth .field-input:focus { outline: none; border-color: var(--ink); }
        .auth .field-hint { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }

        /* shipping group from _signup_fields */
        .auth .shipping-group { border-top: 1px solid var(--rule); padding-top: 18px; margin-top: 18px; }
        .auth .shipping-group .field-label { margin-bottom: 10px; }
        .auth .shipping-group .field-input { margin-bottom: 8px; }
        .auth .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; }
        .auth .shipping-row .field-input { margin-bottom: 8px; }

        /* marketing opt-in */
        .auth .marketing-field { margin-top: 16px; }
        .auth .marketing-label { display: flex; align-items: flex-start; gap: .625rem; cursor: pointer; font-size: 13px; line-height: 1.5; color: var(--ink); }
        .auth .marketing-label input { margin-top: .2rem; width: 17px; height: 17px; flex-shrink: 0; accent-color: var(--accent); }

        .auth .card .btn.block { margin-top: 8px; }
        .auth .alt { margin-top: 20px; font-size: 13px; color: var(--muted); }
        .auth .alt a { color: var(--accent); font-weight: 600; }
        .auth .alt a:hover { text-decoration: underline; }
    </style>

    <main>
        <section class="auth">
            <div class="card reveal">
                <div class="kicker k">{{ $tenant->name }}</div>
                @php
                    // Ember editorial accent: italicise the final word of the (escaped) title.
                    $regTitle = e(__('site.auth.register_title'));
                    $regTitle = preg_replace('/(\S+)\s*$/u', '<em>$1</em>', $regTitle, 1);
                @endphp
                <h1>{!! $regTitle !!}</h1>
                <p class="lede">{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p>

                @if ($errors->any())
                    <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form method="post" action="/account/register">
                    @csrf
                    <div class="field">
                        <label class="field-label" for="name">{{ __('site.auth.full_name') }}</label>
                        <input class="field-input" type="text" name="name" id="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                    </div>
                    <div class="field">
                        <label class="field-label" for="email">{{ __('site.auth.email') }}</label>
                        <input class="field-input" type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                    <div class="field">
                        <label class="field-label" for="password">{{ __('site.auth.password') }}</label>
                        <input class="field-input" type="password" name="password" id="password" required autocomplete="new-password">
                        <span class="field-hint">{{ __('site.auth.password_hint') }}</span>
                    </div>
                    <div class="field">
                        <label class="field-label" for="password_confirmation">{{ __('site.auth.password_confirm') }}</label>
                        <input class="field-input" type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
                    </div>

                    @php
                        $csSignup = $store->signupFieldsConfig();
                        $reqMarker = fn (bool $req) => $req ? '' : ' <span style="color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0;">(' . __('site.auth.optional') . ')</span>';
                    @endphp

                    @include('storefront.auth._signup_fields')

                    <button type="submit" class="btn block accent">{{ __('site.auth.create_account_btn') }}</button>
                </form>

                <div class="alt">
                    {{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }} →</a>
                </div>
            </div>
        </section>
    </main>
@endsection
