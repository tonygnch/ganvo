<?php

namespace App\Http\Controllers;

use App\Support\PanelLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PanelLanguageController extends Controller
{
    /**
     * Save the merchant's panel language and send them back where they were.
     *
     * Stored on the USER rather than in a cookie so the choice follows them to
     * a second browser or machine, which a cookie would not, and so it cannot
     * be lost by clearing site data.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(PanelLocale::isSupported($locale), 404);

        $user = $request->user();
        $user->locale = $locale;
        $user->save();

        return back();
    }
}
