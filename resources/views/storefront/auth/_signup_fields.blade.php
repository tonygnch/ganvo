{{--
    Shared signup-field bodies for the per-theme register pages.

    Each caller theme renders its own chrome (form wrapper, base inputs,
    submit button) and includes this partial in the spot where the optional
    merchant-configured fields should appear. The wrapper class names
    (field, field-label, field-input) are styled per-theme; this partial
    just emits the markup with the theme's existing classes.

    Required context:
      $csSignup  — array from $store->signupFieldsConfig()
      $reqMarker — closure that returns the "(optional)" suffix HTML
                   given a bool required flag. Theme decides its color.
      $tenant    — App\Models\Tenant — used in the marketing-consent copy
--}}

@if ($csSignup['phone']['enabled'])
    <div class="field">
        <label class="field-label" for="phone">
            {{ __('site.auth.phone') }}{!! $reqMarker($csSignup['phone']['required']) !!}
        </label>
        <input class="field-input" type="tel" name="phone" id="phone" value="{{ old('phone') }}"
               @if ($csSignup['phone']['required']) required @endif>
    </div>
@endif

@if ($csSignup['birthday']['enabled'])
    <div class="field">
        <label class="field-label" for="birthday">
            {{ __('site.auth.birthday') }}{!! $reqMarker($csSignup['birthday']['required']) !!}
        </label>
        <input class="field-input" type="date" name="birthday" id="birthday" value="{{ old('birthday') }}"
               @if ($csSignup['birthday']['required']) required @endif>
    </div>
@endif

@if ($csSignup['shipping_address']['enabled'])
    <div class="field shipping-group">
        <label class="field-label">
            {{ __('site.auth.shipping_address') }}{!! $reqMarker($csSignup['shipping_address']['required']) !!}
        </label>
        <input class="field-input" type="text" name="address_line" value="{{ old('address_line') }}"
               placeholder="{{ __('site.auth.address_line') }}"
               @if ($csSignup['shipping_address']['required']) required @endif>
        <div class="shipping-row">
            <input class="field-input" type="text" name="address_city" value="{{ old('address_city') }}"
                   placeholder="{{ __('site.auth.address_city') }}"
                   @if ($csSignup['shipping_address']['required']) required @endif>
            <input class="field-input" type="text" name="address_postal" value="{{ old('address_postal') }}"
                   placeholder="{{ __('site.auth.address_postal') }}"
                   @if ($csSignup['shipping_address']['required']) required @endif>
        </div>
        <input class="field-input" type="text" name="address_country" value="{{ old('address_country') }}"
               placeholder="{{ __('site.auth.address_country') }}"
               maxlength="2" style="text-transform: uppercase;"
               @if ($csSignup['shipping_address']['required']) required @endif>
    </div>
@endif

@if ($csSignup['marketing_optin']['enabled'])
    <div class="field marketing-field">
        <label class="marketing-label">
            <input type="checkbox" name="marketing_optin" value="1"
                   @if (old('marketing_optin')) checked @endif
                   @if ($csSignup['marketing_optin']['required']) required @endif>
            <span>{{ __('site.auth.marketing_consent', ['tenant' => $tenant->name]) }}{!! $reqMarker($csSignup['marketing_optin']['required']) !!}</span>
        </label>
    </div>
@endif
