<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        $customer = Auth::guard('customer')->user();
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->theme($store);

        $orders = $customer->orders()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->limit(20)
            ->get();

        return view(
            $this->view($theme, 'account.index'),
            compact('tenant', 'store', 'theme', 'customer', 'orders')
        );
    }

    /**
     * Account settings form — profile/address + password change.
     */
    public function settings(): View|RedirectResponse
    {
        if (! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        $customer = Auth::guard('customer')->user();
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = $this->theme($store);

        $countries = \App\Services\Countries::all();

        return view(
            $this->view($theme, 'account.settings'),
            compact('tenant', 'store', 'theme', 'customer', 'countries')
        );
    }

    /**
     * Update profile fields (name, contact, default shipping address,
     * marketing preference). Email changes are allowed but must stay
     * unique within the tenant.
     */
    public function updateProfile(\Illuminate\Http\Request $request): RedirectResponse
    {
        if (! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        $customer = Auth::guard('customer')->user();
        $tenant = app('current_tenant');

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => [
                'required', 'email', 'max:190',
                // Email is unique per-tenant (the same address can exist
                // across different stores), excluding this customer's row.
                Rule::unique('customers', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($customer->id),
            ],
            'phone'     => ['nullable', 'string', 'max:40'],
            'birthday'  => ['nullable', 'date'],
            'address_line'   => ['nullable', 'string', 'max:200'],
            'city'           => ['nullable', 'string', 'max:120'],
            'postal_code'    => ['nullable', 'string', 'max:30'],
            'address_region' => ['nullable', 'string', 'max:120'],
            'country'        => ['nullable', 'string', 'max:2'],
            'marketing_optin' => ['nullable', 'boolean'],
        ]);

        $customer->name = $data['name'];
        $customer->email = $data['email'];
        $customer->phone = $data['phone'] ?? null;
        $customer->birthday = $data['birthday'] ?? null;

        // Build the default shipping address only when at least the line +
        // city are present; otherwise clear it.
        if (! empty($data['address_line']) && ! empty($data['city'])) {
            $customer->default_shipping_address = [
                'line'        => $data['address_line'],
                'city'        => $data['city'],
                'postal_code' => $data['postal_code'] ?? '',
                'region'      => $data['address_region'] ?? '',
                'country'     => $data['country'] ?? '',
            ];
        } else {
            $customer->default_shipping_address = null;
        }

        // Marketing opt-in is a timestamp: set on first opt-in, preserve the
        // original timestamp if still opted in, clear when unchecked.
        $optedIn = (bool) ($data['marketing_optin'] ?? false);
        if ($optedIn && ! $customer->marketing_optin_at) {
            $customer->marketing_optin_at = now();
        } elseif (! $optedIn) {
            $customer->marketing_optin_at = null;
        }

        $customer->save();

        return redirect('/account/settings')
            ->with('account.flash', __('site.account.settings_saved'));
    }

    /**
     * Change password — requires the current password, new must be confirmed.
     */
    public function updatePassword(\Illuminate\Http\Request $request): RedirectResponse
    {
        if (! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        $customer = Auth::guard('customer')->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->input('current_password'), $customer->password)) {
            return redirect('/account/settings')
                ->withErrors(['current_password' => __('site.account.wrong_password')], 'password')
                ->with('account.password_open', true);
        }

        $customer->password = $request->input('password'); // hashed via cast
        $customer->save();

        return redirect('/account/settings')
            ->with('account.flash', __('site.account.password_changed'));
    }

    private function theme($store): string
    {
        return ThemeRegistry::exists($store->theme) ? $store->theme : 'default';
    }

    /**
     * Resolve a theme-specific account view, falling back to the generic one.
     */
    private function view(string $theme, string $relative): string
    {
        $candidate = "themes.{$theme}.{$relative}";
        return view()->exists($candidate) ? $candidate : "storefront.{$relative}";
    }
}
