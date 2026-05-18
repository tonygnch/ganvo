@php $title = __('site.onboarding.business.title'); @endphp
@extends('onboarding.layout')

@section('content')
    <div class="panel">
        <p class="panel-eyebrow">{{ __('site.onboarding.business.eyebrow') }}</p>
        <h1>{{ __('site.onboarding.business.title') }}</h1>
        <p class="lead">{{ __('site.onboarding.business.lead') }}</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="/onboarding/business">
            @csrf

            <div class="field">
                <label class="lbl" for="name">{{ __('site.onboarding.business.store_name') }}</label>
                <input class="input" type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required autofocus>
                <p class="help">{{ __('site.onboarding.business.store_name_help', ['slug' => $tenant->slug]) }}</p>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="lbl" for="business_type">{{ __('site.onboarding.business.business_type') }}</label>
                    <select class="input" name="business_type" id="business_type" required>
                        <option value="" disabled @if(! old('business_type', $tenant->business_type) || $tenant->business_type === 'other') selected @endif>{{ __('site.onboarding.business.business_type_ph') }}</option>
                        @foreach ($businessTypes as $key => $label)
                            <option value="{{ $key }}" @if(old('business_type', $tenant->business_type) === $key) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="lbl" for="currency">{{ __('site.onboarding.business.currency') }}</label>
                    <select class="input" name="currency" id="currency" required>
                        @foreach ($currencies as $key => $label)
                            <option value="{{ $key }}" @if(old('currency', $store->currency ?? 'USD') === $key) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="help">{{ __('site.onboarding.business.currency_help') }}</p>
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label class="lbl" for="contact_email">{{ __('site.onboarding.business.contact_email') }}</label>
                    <input class="input" type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $tenant->contact_email) }}" required>
                </div>
                <div class="field">
                    <label class="lbl" for="contact_phone">{{ __('site.onboarding.business.contact_phone') }}</label>
                    <input class="input" type="tel" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $tenant->contact_phone) }}" placeholder="+1 555 0100">
                    <p class="help">{{ __('site.onboarding.business.contact_phone_help') }}</p>
                </div>
            </div>

            <div class="actions">
                <a href="/" class="btn btn-ghost">{{ __('site.onboarding.business.skip_home') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('site.onboarding.business.cta') }} →</button>
            </div>
        </form>
    </div>
@endsection
