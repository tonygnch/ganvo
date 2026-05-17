<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect('/account');
        }

        return view('storefront.auth.login', $this->viewData());
    }

    public function login(Request $request): RedirectResponse
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

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

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

        return view('storefront.auth.register', $this->viewData());
    }

    public function register(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');
        if (! $tenant->store->allow_registration) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // hashed by cast
        ]);

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return redirect('/account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerate();

        return redirect('/');
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
}
