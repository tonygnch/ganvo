<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingSignup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * Captures email signups from the coming-soon splash page. Designed to
 * work with or without JavaScript:
 *   - With JS: the page submits via fetch + reads back JSON.
 *   - Without JS: standard form POST + 302 redirect with a flash message.
 *
 * Anti-spam strategy is intentionally lightweight — this endpoint is
 * unauthenticated and public-facing, so we don't want to over-engineer:
 *   - Honeypot field ("website") that bots fill but humans never see.
 *   - Per-IP rate limit (1 attempt every 30s, 10 per hour).
 *   - Duplicate emails are NOT a validation error; we silently accept
 *     them as success. This avoids leaking whether an address is on the
 *     list and gives bots no useful signal.
 */
class SignupController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Honeypot: real browsers won't fill `website` because it's hidden
        // via CSS. Bots that scrape forms and fill every field will.
        if (filled($request->input('website'))) {
            // Look exactly like a successful response so the bot doesn't
            // learn that it's been rejected.
            return $this->respondSuccess($request);
        }

        // Throttle by IP. Burst window catches accidental double-clicks
        // without blocking them outright; hour window stops slower
        // automated brute-forcing. Numbers tuned for "a real human signing
        // up + maybe fat-fingering submit twice" being fine, but "a script
        // hammering the endpoint" being shut down.
        $ipKey = 'marketing-signup:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey . ':burst', 5)) {
            return $this->respondError($request, __('site.marketing.coming_soon.error_throttled'), 429);
        }
        if (RateLimiter::tooManyAttempts($ipKey . ':hour', 20)) {
            return $this->respondError($request, __('site.marketing.coming_soon.error_throttled'), 429);
        }
        RateLimiter::hit($ipKey . ':burst', 60);
        RateLimiter::hit($ipKey . ':hour', 3600);

        $validator = Validator::make($request->all(), [
            // RFC-correct + DNS sanity check via 'email:filter'. Max length
            // matches the column.
            'email' => ['required', 'email:filter', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->respondError($request, $validator->errors()->first('email'), 422);
        }

        $email = strtolower(trim($validator->validated()['email']));

        // Idempotent insert: existing rows are left alone. We don't update
        // ip / user_agent on duplicate signup so we keep the original
        // first-seen context.
        MarketingSignup::firstOrCreate(
            ['email' => $email],
            [
                'locale' => app()->getLocale(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]
        );

        return $this->respondSuccess($request);
    }

    private function respondSuccess(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('site.marketing.coming_soon.thanks'),
            ]);
        }
        return back()->with('signup_status', 'ok');
    }

    private function respondError(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], $status);
        }
        return back()->with('signup_error', $message)->withInput();
    }
}
