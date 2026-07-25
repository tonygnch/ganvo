<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP Basic auth across the whole platform, for showing work in progress on a
 * public domain without it being publicly readable.
 *
 * Off unless PREVIEW_LOCK=true, so local dev and a real launch are unaffected.
 *
 * WHAT IT DELIBERATELY DOES NOT LOCK
 *
 *   /up                       the health check the host/uptime monitor polls;
 *                             locking it turns every check into a 401 page
 *   stripe/webhook            Cashier's subscription webhook
 *   webhooks/stripe/connect   the Connect payment webhook
 *
 * Both webhooks are server-to-server POSTs from Stripe that cannot carry Basic
 * credentials. Locking them would make Stripe retry, then disable the endpoint,
 * and payments would silently stop reconciling. They verify their own
 * signatures, which is the real protection.
 *
 * THE PASSWORD IS NEVER STORED IN PLAIN TEXT. The env holds a bcrypt hash;
 * generate it with `php artisan ganvo:preview-password`, which prompts without
 * echoing so the password never reaches shell history or a file.
 *
 * This is a soft gate — it keeps a preview out of search results and away from
 * casual visitors. It is not a substitute for real authorisation on anything
 * that matters; the admin panels keep their own logins underneath it.
 */
class PreviewGate
{
    /** Paths that must answer normally even while the gate is up. */
    private const OPEN = [
        'up',
        'stripe/webhook',
        'webhooks/stripe/connect',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('ganvo.preview.enabled')) {
            return $next($request);
        }

        foreach (self::OPEN as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        $user = (string) config('ganvo.preview.user');
        $hash = (string) config('ganvo.preview.password_hash');

        // Misconfigured (lock on, no credentials set) must FAIL CLOSED. Failing
        // open would silently publish the very thing the flag was turned on to
        // hide, and the mistake would be invisible until someone found the site.
        if ($user === '' || $hash === '') {
            return $this->challenge('Preview lock is enabled but PREVIEW_USER / PREVIEW_PASSWORD_HASH are not set.');
        }

        $givenUser = (string) $request->getUser();
        $givenPass = (string) $request->getPassword();

        // hash_equals on the username so a wrong name and a wrong password cost
        // the same time; Hash::check is already constant-time.
        // Hash::check THROWS when the stored value is not a bcrypt hash — and a
        // hash pasted into .env is easy to mangle, because it is full of $
        // signs that a shell or a careless quote will eat. Unguarded, a typo
        // there answers every request with a 500 (and a stack trace, if debug
        // is on) instead of the password prompt. A hash we cannot read is not
        // a hash that can match: treat it as a failed attempt and keep asking.
        try {
            $ok = $givenUser !== ''
                && hash_equals($user, $givenUser)
                && Hash::check($givenPass, $hash);
        } catch (\Throwable $e) {
            $ok = false;
        }

        if (! $ok) {
            return $this->challenge();
        }

        $response = $next($request);

        // Belt and braces: a preview must never end up in an index, even if a
        // crawler somehow gets past the gate or a logged-in person shares a URL.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);

        return $response;
    }

    private function challenge(string $note = ''): Response
    {
        return response($note !== '' ? $note : 'Authentication required.', 401, [
            'WWW-Authenticate' => 'Basic realm="' . addslashes((string) config('app.name', 'Preview')) . '", charset="UTF-8"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
