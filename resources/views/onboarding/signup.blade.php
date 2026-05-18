@php $title = __('site.onboarding.signup.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <div class="panel">
        <p class="panel-eyebrow">{{ __('site.onboarding.signup.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.signup.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.signup.lead') }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/signup">
            @csrf

            <div class="field">
                <label class="lbl" for="business_name">{{ __('site.onboarding.signup.business_name') }}</label>
                <input class="input" type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" placeholder="{{ __('site.onboarding.signup.business_name_ph') }}" required autofocus>
                <p class="help">{{ __('site.onboarding.signup.business_name_help') }}</p>
            </div>

            <div class="field">
                <label class="lbl" for="name">{{ __('site.onboarding.signup.your_name') }}</label>
                <input class="input" type="text" name="name" id="name" value="{{ old('name') }}" required>
            </div>

            <div class="field">
                <label class="lbl" for="email">{{ __('site.onboarding.signup.email') }}</label>
                <input class="input" type="email" name="email" id="email" value="{{ old('email') }}" required>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="lbl" for="password">{{ __('site.onboarding.signup.password') }}</label>
                    <input class="input" type="password" name="password" id="password" required>
                </div>
                <div class="field">
                    <label class="lbl" for="password_confirmation">{{ __('site.onboarding.signup.password_confirm') }}</label>
                    <input class="input" type="password" name="password_confirmation" id="password_confirmation" required>
                </div>
            </div>

            <div class="actions">
                <a href="/" class="btn btn-ghost">← {{ __('site.onboarding.signup.back') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('site.onboarding.signup.cta') }} →</button>
            </div>
        </form>

        <div class="muted-line">
            {{ __('site.onboarding.signup.have_account') }}
            <a href="/onboarding/login">{{ __('site.common.sign_in') }}</a>
        </div>
    </div>
@endsection
