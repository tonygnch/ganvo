<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $locale = $request->route('locale');
        // The list for THIS host: /lang/en is a real switch on ganvo.bg and a
        // 404 on a client's shop, which offers Bulgarian only.
        abort_unless(in_array($locale, SetLocale::supportedFor($request), true), 404);

        Cookie::queue(SetLocale::COOKIE, $locale, 60 * 24 * 365);

        $referer = $request->headers->get('referer');
        return redirect($referer ?: '/');
    }
}
