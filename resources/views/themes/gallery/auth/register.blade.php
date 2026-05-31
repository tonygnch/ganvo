@php $title = __('site.common.create_account'); @endphp
@extends('themes.gallery.layout')

@section('content')
    <style>
        .auth { max-width: 480px; margin: 0 auto; padding: 64px 40px 96px; }
        .auth-head { text-align: center; margin-bottom: 26px; }
        .auth-head .eyebrow { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .auth-head h1 { font-family: var(--display); font-weight: 700; font-size: clamp(30px,5vw,42px); letter-spacing: -.02em; margin-top: 12px; }
        .auth-head p { color: var(--muted); font-size: 14px; margin-top: 8px; }
        .auth-card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 36px; }
        .errors { border: 1px solid #c2705a; background: #f3e2da; color: #8a3f2c; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 13px; } .errors ul { padding-left: 18px; }
        .field { margin-bottom: 16px; }
        .field-label { display: block; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; } .field-label small { text-transform: none; letter-spacing: 0; }
        .field-input { width: 100%; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); padding: 14px; font-family: inherit; font-size: 14px; }
        .field-input:focus { outline: none; border-color: var(--accent); }
        .field-hint { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
        .shipping-group { border-top: 1px solid var(--line); padding-top: 20px; margin-top: 20px; }
        .shipping-row { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; }
        .marketing-field { margin-top: 14px; } .marketing-label { display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: var(--muted); cursor: pointer; } .marketing-label input { accent-color: var(--accent); margin-top: 3px; }
        .auth-submit { width: 100%; background: var(--accent); color: #faf7f1; border: 0; border-radius: 8px; padding: 15px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; }
        .auth-foot { text-align: center; margin-top: 24px; font-size: 14px; color: var(--muted); } .auth-foot a { color: var(--accent); font-weight: 600; }
    </style>

    <main>
        <div class="auth rv">
            <div class="auth-head"><div class="eyebrow">{{ $tenant->name }}</div><h1>{{ __('site.auth.register_title') }}</h1><p>{{ __('site.auth.register_lead', ['tenant' => $tenant->name]) }}</p></div>
            <div class="auth-card">
                @if ($errors->any())<div class="errors"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <form method="post" action="/account/register">
                    @csrf
                    <div class="field"><label class="field-label" for="name">{{ __('site.auth.full_name') }}</label><input class="field-input" type="text" name="name" id="name" value="{{ old('name') }}" required autofocus></div>
                    <div class="field"><label class="field-label" for="email">{{ __('site.auth.email') }}</label><input class="field-input" type="email" name="email" id="email" value="{{ old('email') }}" required></div>
                    <div class="field"><label class="field-label" for="password">{{ __('site.auth.password') }}</label><input class="field-input" type="password" name="password" id="password" required><span class="field-hint">{{ __('site.auth.password_hint') }}</span></div>
                    <div class="field"><label class="field-label" for="password_confirmation">{{ __('site.auth.password_confirm') }}</label><input class="field-input" type="password" name="password_confirmation" id="password_confirmation" required></div>
                    @php $csSignup = $store->signupFieldsConfig(); $reqMarker = fn (bool $req) => $req ? '' : ' <small>(' . __('site.auth.optional') . ')</small>'; @endphp
                    @include('storefront.auth._signup_fields')
                    <button type="submit" class="auth-submit">{{ __('site.auth.create_account_btn') }}</button>
                </form>
            </div>
            <div class="auth-foot">{{ __('site.auth.have_account') }} <a href="/account/login">{{ __('site.auth.sign_in_link') }}</a></div>
        </div>
    </main>
@endsection
