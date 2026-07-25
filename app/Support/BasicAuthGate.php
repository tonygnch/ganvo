<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * The comparison and the 401 challenge behind both preview locks — the
 * platform-wide env one (PreviewGate) and the per-storefront one configured in
 * the Super Admin panel (StorefrontPreviewGate).
 *
 * Kept in one place because the interesting parts are easy to get subtly wrong
 * and must not drift between the two:
 *
 *   · a wrong username and a wrong password have to cost the same time, or the
 *     response time tells an attacker when they have found the username;
 *   · Hash::check THROWS on anything that is not a bcrypt hash, and a hash
 *     typed or pasted into a config field is easy to mangle — unguarded, one
 *     bad character answers every request with a 500 instead of a prompt;
 *   · a lock that cannot read its own credentials must refuse, never open.
 */
class BasicAuthGate
{
    /**
     * True only when the request carries credentials matching this user + hash.
     * Never throws: any malformed stored hash is a failed attempt.
     */
    public static function passes(Request $request, string $user, string $hash): bool
    {
        if ($user === '' || $hash === '') {
            return false;
        }

        $givenUser = (string) $request->getUser();
        $givenPass = (string) $request->getPassword();

        if ($givenUser === '') {
            return false;
        }

        try {
            // hash_equals first for the name, so both halves are constant-time.
            return hash_equals($user, $givenUser) && Hash::check($givenPass, $hash);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** The 401 that makes the browser show its username/password prompt. */
    public static function challenge(string $realm, string $note = ''): Response
    {
        return response($note !== '' ? $note : 'Authentication required.', 401, [
            'WWW-Authenticate' => 'Basic realm="' . addslashes($realm) . '", charset="UTF-8"',
            // A preview must never be indexed, even by a crawler that somehow
            // holds credentials or follows a shared URL.
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
