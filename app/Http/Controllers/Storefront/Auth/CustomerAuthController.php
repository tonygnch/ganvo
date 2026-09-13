<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RackConfiguration;
use App\Themes\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Customer sign-in and sign-up — as full pages, and as JSON for the rack
 * configurator's in-place window, which must not navigate away from a drawing
 * the visitor has not saved yet. Same rules either way; only the reply differs.
 */
class CustomerAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect('/account');
        }

        return view($this->themedView('auth.login', 'storefront.auth.login'), $this->viewData());
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = app('current_tenant');

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('tenant_id', $tenant->id)
            ->where('email', strtolower($credentials['email']))
            ->first();

        if (! $customer || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => __('site.auth.bad_credentials'),
            ]);
        }

        $this->signIn($request, $customer);

        if ($request->expectsJson()) {
            return $this->signedIn($customer);
        }

        return redirect()->intended('/account');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect('/account');
        }

        $tenant = app('current_tenant');
        if (! $tenant->store->allow_registration) {
            abort(404);
        }

        return view($this->themedView('auth.register', 'storefront.auth.register'), $this->viewData());
    }

    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = app('current_tenant');
        if (! $tenant->store->allow_registration) {
            abort(404);
        }

        // Build the validation rule set dynamically from the merchant's
        // configured signup fields. Optional fields only validate when
        // present; required fields validate even when blank.
        $signupConfig = $tenant->store->signupFieldsConfig();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            // The configurator's quick form asks for the password once, with a
            // "show" toggle instead of a second box — typing it twice in a
            // small window is exactly the friction that window exists to cut.
            'password' => $request->expectsJson()
                ? ['required', 'string', 'min:8']
                : ['required', 'string', 'min:8', 'confirmed'],
        ];
        $opt = fn (bool $required) => $required ? ['required'] : ['nullable'];
        if ($signupConfig['phone']['enabled']) {
            $rules['phone'] = array_merge($opt($signupConfig['phone']['required']), ['string', 'max:32']);
        }
        if ($signupConfig['birthday']['enabled']) {
            $rules['birthday'] = array_merge($opt($signupConfig['birthday']['required']), ['date', 'before:today']);
        }
        if ($signupConfig['shipping_address']['enabled']) {
            $r = $opt($signupConfig['shipping_address']['required']);
            $rules['address_line']  = array_merge($r, ['string', 'max:255']);
            $rules['address_city']  = array_merge($r, ['string', 'max:120']);
            $rules['address_postal']= array_merge($r, ['string', 'max:32']);
            $rules['address_country'] = array_merge($r, ['string', 'size:2']);
        }
        if ($signupConfig['marketing_optin']['enabled'] && $signupConfig['marketing_optin']['required']) {
            $rules['marketing_optin'] = ['accepted']; // required-checkbox semantics
        }

        $data = $request->validate($rules, [
            // The generic "already taken" leaves somebody who simply forgot
            // they had an account with nowhere to go.
            'email.unique' => __('site.auth.email_taken'),
        ]);

        $attrs = [
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // hashed by cast
        ];
        if ($signupConfig['phone']['enabled'] && ! empty($data['phone'])) {
            $attrs['phone'] = $data['phone'];
        }
        if ($signupConfig['birthday']['enabled'] && ! empty($data['birthday'])) {
            $attrs['birthday'] = $data['birthday'];
        }
        if ($signupConfig['shipping_address']['enabled']
            && ! empty($data['address_line'])
            && ! empty($data['address_city'])
            && ! empty($data['address_postal'])
            && ! empty($data['address_country'])
        ) {
            $attrs['default_shipping_address'] = [
                'line'        => $data['address_line'],
                'city'        => $data['address_city'],
                'postal_code' => $data['address_postal'],
                'country'     => strtoupper($data['address_country']),
            ];
        }
        // Marketing opt-in is a checkbox: present + truthy = consent given now.
        if ($signupConfig['marketing_optin']['enabled'] && $request->boolean('marketing_optin')) {
            $attrs['marketing_optin_at'] = now();
        }

        $customer = Customer::create($attrs);

        $this->signIn($request, $customer);

        if ($request->expectsJson()) {
            return $this->signedIn($customer);
        }

        return redirect('/account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerate();

        return redirect('/');
    }

    /**
     * Log in, rotate the session, and bring along any racks this browser saved
     * before it had an account.
     *
     * The order matters. The racks are found by a hash of the session id, and
     * logging in replaces that id — the guard migrates the session inside
     * login(), before regenerate() ever runs — so the hash is taken before
     * either, while it still points at them.
     */
    private function signIn(Request $request, Customer $customer): void
    {
        $guestRacks = RackConfiguration::ownerToken();

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        RackConfiguration::claimForCustomer($customer, $guestRacks);
    }

    /**
     * The JSON reply. regenerate() also rotated the CSRF token, so the page
     * gets the new one — without it, the very save the visitor signed up to
     * make would bounce with a 419.
     */
    private function signedIn(Customer $customer): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'token' => csrf_token(),
            'name' => $customer->name,
        ]);
    }

    private function viewData(): array
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        return [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => ThemeRegistry::exists($store->theme) ? $store->theme : 'default',
        ];
    }

    /**
     * Prefer themes.{theme}.{view} when it exists, fall back to the shared
     * default. Mirrors the same hook CartController + CheckoutController use,
     * so a theme can opt into a full custom auth UI just by dropping in the
     * matching Blade file — no controller changes needed.
     */
    private function themedView(string $themeRelative, string $fallback): string
    {
        $tenant = app('current_tenant');
        $themeSlug = ThemeRegistry::exists($tenant->store->theme) ? $tenant->store->theme : 'default';
        $candidate = "themes.{$themeSlug}.{$themeRelative}";
        return view()->exists($candidate) ? $candidate : $fallback;
    }
}
