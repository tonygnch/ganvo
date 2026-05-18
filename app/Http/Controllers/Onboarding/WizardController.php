<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Top-level wizard entry point: figures out which step the merchant is on
 * and redirects to the matching route. Individual step controllers will be
 * added in subsequent slices.
 *
 * Acting as the single source of truth for "where should this merchant be
 * right now?" keeps all step UIs simple — they just render their form and
 * call $tenant->advanceOnboarding() on save.
 */
class WizardController extends Controller
{
    public function entry(): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            // Logged in but no tenant — shouldn't happen post-signup, but
            // catch it gracefully rather than dying with a null deref.
            return redirect('/onboarding/signup');
        }

        if ($tenant->isOnboarded()) {
            return redirect('/store');
        }

        return redirect('/onboarding/' . $tenant->onboarding_step);
    }
}
