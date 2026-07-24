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
     * Bulgarian first: it is the platform's primary language, and the order
     * here is load-bearing twice over — it drives the language switcher's
     * listing, and Symfony's getPreferredLanguage() falls back to the FIRST
     * entry when a request carries no usable Accept-Language header.
     */
    public const SUPPORTED = ['bg', 'en'];
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
