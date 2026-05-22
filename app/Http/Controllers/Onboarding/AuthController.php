<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Merchant-side authentication. Handles the entry point of the onboarding
 * wizard (signup) plus login/logout. Distinct from the Filament built-in auth
 * pages — Filament's `->registration()` is intentionally disabled so this
 * flow owns merchant signup end-to-end.
 *
 * Signup creates the User AND a freshly-pending Tenant + Store row so the
 * wizard has somewhere to write progressive state. The user is logged in
 * immediately and redirected to /onboarding to start the wizard.
 */
class AuthController extends Controller
{
    public function showSignup(): View
    {
        return view('onboarding.signup');
    }

    public function signup(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            // Optional — set by the marketing page's plan cards via a hidden
            // input. Ignored if not a real active plan slug.
            'plan'          => ['nullable', 'string', 'max:60'],
            'billing_period'=> ['nullable', 'in:monthly,yearly'],
        ])->validate();

        // Resolve a starting plan from the carried-through marketing click.
        // Default to 'starter' so existing flows are unchanged.
        $startingPlan = 'starter';
        if (! empty($data['plan'])) {
            $exists = \App\Models\Plan::where('slug', $data['plan'])->where('is_active', true)->exists();
            if ($exists) {
                $startingPlan = $data['plan'];
            }
        }
        $startingPeriod = $data['billing_period'] ?? 'monthly';

        $user = DB::transaction(function () use ($data, $startingPlan, $startingPeriod) {
            $tenant = Tenant::create([
                'name'             => $data['business_name'],
                'slug'             => $this->uniqueSlug($data['business_name']),
                'business_type'    => 'other',
                'contact_email'    => $data['email'],
                'subscription_plan' => $startingPlan,
                'billing_period'   => $startingPeriod,
                'status'           => Tenant::STATUS_PENDING,
                'onboarding_step'  => 'business',
            ]);

            Store::create([
                'tenant_id'           => $tenant->id,
                'theme'               => 'default',
                'primary_color'       => '#10B981',
                'secondary_color'     => '#1F2937',
                'font_family'         => 'Inter',
                'currency'            => 'EUR',
                'display_currencies'  => ['EUR'],
                'fx_rates'            => [],
                'is_live'             => false,
                'checkout_mode'       => Store::CHECKOUT_BOTH,
                'allow_registration'  => true,
            ]);

            $user = User::create([
                'tenant_id'         => $tenant->id,
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => Hash::make($data['password']),
                // In local dev we auto-verify; production should send the
                // verification mail and gate the wizard on `email_verified_at`.
                'email_verified_at' => app()->environment('local') ? now() : null,
            ]);

            $role = Role::firstOrCreate(['name' => 'store_admin']);
            $user->assignRole($role);

            return $user;
        });

        Auth::login($user, remember: true);

        return redirect('/onboarding');
    }

    public function showLogin(): View
    {
        return view('onboarding.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ])->validate();

        if (! Auth::attempt($credentials, remember: true)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('site.onboarding.login.invalid')]);
        }

        $request->session()->regenerate();

        // Where to land depends on whether they finished onboarding.
        $user = Auth::user();
        if (! $user->tenant || $user->tenant->isOnboarded()) {
            return redirect('/store');
        }
        return redirect('/onboarding');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    /**
     * Pick a tenant slug derived from the business name. Slugs are URL-visible
     * (acme.ganvo.lvh.me), so we want them stable and unique — append a short
     * random suffix on collision rather than counting up (less prediction).
     */
    private function uniqueSlug(string $businessName): string
    {
        $base = Str::slug($businessName) ?: 'store';
        $slug = $base;
        $attempts = 0;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(4));
            $attempts++;
            if ($attempts > 10) {
                // Pathological — bail to a fully random fallback.
                $slug = 'store-' . Str::lower(Str::random(8));
                break;
            }
        }
        return $slug;
    }
}
