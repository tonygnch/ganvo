@php $title = __('site.onboarding.login.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <div class="panel">
        <p class="panel-eyebrow">{{ __('site.onboarding.login.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.login.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.login.lead') }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/login">
            @csrf
            <div class="field">
                <label class="lbl" for="email">{{ __('site.onboarding.login.email') }}</label>
                <input class="input" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label class="lbl" for="password">{{ __('site.onboarding.login.password') }}</label>
                <input class="input" type="password" name="password" id="password" required>
            </div>
            <div class="actions">
                <a href="/" class="btn btn-ghost">← {{ __('site.onboarding.login.back') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('site.onboarding.login.cta') }}</button>
            </div>
        </form>

        <div class="muted-line">
            {{ __('site.onboarding.login.no_account') }}
            <a href="/onboarding/signup">{{ __('site.common.start_free') }}</a>
        </div>
    </div>
@endsection
