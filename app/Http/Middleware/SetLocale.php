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
    public const SUPPORTED = ['en', 'bg'];
    public const DEFAULT = 'en';

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
