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
     * TWO SITES LIVE BEHIND THIS MIDDLEWARE, AND THEY DO NOT GET THE SAME
     * ANSWER.
     *
     * The `web` group covers both ganvo.bg — our own studio site — and every
     * client storefront on a *.ganvo.bg subdomain. There used to be ONE list
     * here, so when English came off the storefronts it came off our own
     * marketing site with it, and ganvo.bg went Bulgarian-only overnight. That
     * was never the intent: a client's shop speaks to that client's customers,
     * while ganvo.bg speaks to anyone deciding whether to hire us.
     *
     * Nothing had to be recovered to undo it — lang/en/site.php still holds
     * every marketing string, and the /en route and the header switcher were
     * both still wired. Only this list said no.
     *
     * SITE        ganvo.bg: Bulgarian and English, switcher shown.
     * STOREFRONT  a client's shop: Bulgarian only. The themes hide their
     *             switcher when it would offer a single language, so one entry
     *             removes the control rather than shipping a dead dropdown.
     *
     * ORDER IS LOAD-BEARING in both: Symfony's getPreferredLanguage() falls
     * back to the FIRST entry when a request carries no usable Accept-Language
     * header, so Bulgarian stays at the head.
     */
    public const SITE = ['bg', 'en'];

    public const STOREFRONT = ['bg'];

    /**
     * Every locale the PLATFORM'S OWN CONTENT is authored in — what the Super
     * Admin content editors offer a field for, regardless of which site ends up
     * serving it. Not what a given visitor may switch between; that is
     * supportedFor().
     */
    public const SUPPORTED = self::SITE;

    public const DEFAULT = 'bg';

    /**
     * What THIS request's site offers a visitor.
     *
     * Decided on the host rather than on a resolved tenant: this middleware
     * runs in the `web` group, which is before the storefront route group's own
     * middleware, so at this point nothing has looked a tenant up yet. Anything
     * that is not exactly the central domain is a client's shop.
     */
    public static function supportedFor(?Request $request = null): array
    {
        $request ??= request();
        $central = (string) config('ganvo.central_domain');
        $host = $request?->getHost() ?? $central;

        return in_array($host, [$central, 'www.' . $central], true)
            ? self::SITE
            : self::STOREFRONT;
    }

    /**
     * @return array<string, string> [code => native display name]
     */
    public static function available(): array
    {
        $list = [];
        foreach (self::supportedFor() as $code) {
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
        $supported = self::supportedFor($request);

        $query = $request->query('lang');
        if (is_string($query) && in_array($query, $supported, true)) {
            Cookie::queue(self::COOKIE, $query, 60 * 24 * 365);
            return $query;
        }

        $cookie = $request->cookie(self::COOKIE);
        if (is_string($cookie) && in_array($cookie, $supported, true)) {
            return $cookie;
        }

        $preferred = $request->getPreferredLanguage($supported);
        if (is_string($preferred) && in_array($preferred, $supported, true)) {
            return $preferred;
        }

        return self::DEFAULT;
    }
}
