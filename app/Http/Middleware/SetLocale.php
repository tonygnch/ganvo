<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const COOKIE = 'ganvo_locale';

    /**
     * Bulgarian ONLY, for now. The owner asked for English to come off the
     * storefronts until it is worth maintaining properly.
     *
     * Nothing was deleted to do this: lang/en/*.php all stay on disk, so
     * putting 'en' back in this array is the entire job of re-enabling it.
     * Everything downstream keys off this list —
     *
     *   - SetLocale::available() feeds every theme's language switcher, and
     *     the themes only render that control when it offers more than one
     *     language, so a single entry hides it rather than shipping a
     *     one-option dropdown;
     *   - LanguageController::switch() 404s anything not in here, so a
     *     bookmarked /lang/en stops working rather than half-working;
     *   - resolve() ignores a stale ganvo_locale=en cookie for the same
     *     reason, so a returning visitor who had picked English lands on
     *     Bulgarian instead of being stuck on a language that no longer
     *     resolves.
     *
     * The ORDER is still load-bearing for the day a second language returns:
     * Symfony's getPreferredLanguage() falls back to the FIRST entry when a
     * request carries no usable Accept-Language header, so Bulgarian must
     * stay at the head of the list.
     */
    public const SUPPORTED = ['bg'];
    public const DEFAULT = 'bg';

    /**
     * @return array<string, string> [code => native display name]
     */
    public static function available(): array
    {
        $list = [];
        foreach (self::SUPPORTED as $code) {
            $key = 'site.lang.' . $code;
            $name = __($key);
            $list[$code] = $name === $key ? strtoupper($code) : $name;
        }
        return $list;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);
        App::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $query = $request->query('lang');
        if (is_string($query) && in_array($query, self::SUPPORTED, true)) {
            Cookie::queue(self::COOKIE, $query, 60 * 24 * 365);
            return $query;
        }

        $cookie = $request->cookie(self::COOKIE);
        if (is_string($cookie) && in_array($cookie, self::SUPPORTED, true)) {
            return $cookie;
        }

        $preferred = $request->getPreferredLanguage(self::SUPPORTED);
        if (is_string($preferred) && in_array($preferred, self::SUPPORTED, true)) {
            return $preferred;
        }

        return self::DEFAULT;
    }
}
