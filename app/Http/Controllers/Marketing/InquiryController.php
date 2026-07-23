<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ProjectInquiry;
use App\Notifications\ProjectInquiryReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * Captures "Start a project" inquiries from the marketing homepage — the
 * studio's lead form. Same public-endpoint hardening as the waitlist
 * (SignupController): honeypot, per-IP throttle, works with or without JS.
 *
 *   - With JS: submitted via fetch, reads back JSON.
 *   - Without JS: standard POST + 302 back with a flash message.
 *
 * The lead is always persisted; a best-effort mail notification is sent to
 * the studio owner (config('ganvo.owner_email')) but its failure never
 * breaks the submission.
 */
class InquiryController extends Controller
{
    /** Curated select options — the form submits these stable keys; labels live in i18n. */
    public const PROJECT_TYPES = ['website', 'redesign', 'webapp', 'other'];
    public const BUDGETS = ['under-5k', '5-15k', '15-40k', '40k-plus', 'unsure'];

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Honeypot: `website` is hidden from humans via CSS; bots fill it.
        // Mimic success so the bot learns nothing.
        if (filled($request->input('website'))) {
            return $this->respondSuccess($request);
        }

        // Throttle by IP: a burst window absorbs accidental double-submits,
        // an hour window shuts down scripted abuse.
        $ipKey = 'project-inquiry:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipKey . ':burst', 5)
            || RateLimiter::tooManyAttempts($ipKey . ':hour', 20)) {
            return $this->respondError($request, __('site.marketing.contact.error_throttled'), 429);
        }
        RateLimiter::hit($ipKey . ':burst', 60);
        RateLimiter::hit($ipKey . ':hour', 3600);

        // Cloudflare Turnstile — only enforced when keys are configured, so
        // dev and keyless deploys keep working on honeypot + rate limits alone.
        if (! $this->passesTurnstile($request)) {
            return $this->respondError($request, __('site.marketing.contact.error_captcha'), 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:255'],
            'company' => ['nullable', 'string', 'max:160'],
            'project_type' => ['nullable', 'in:' . implode(',', self::PROJECT_TYPES)],
            'budget' => ['nullable', 'in:' . implode(',', self::BUDGETS)],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        if ($validator->fails()) {
            return $this->respondError($request, $validator->errors()->first(), 422);
        }

        $data = $validator->validated();

        $inquiry = ProjectInquiry::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'company' => isset($data['company']) ? trim($data['company']) : null,
            'project_type' => $data['project_type'] ?? null,
            'budget' => $data['budget'] ?? null,
            'message' => trim($data['message']),
            'status' => ProjectInquiry::STATUS_NEW,
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        // Notify the studio owner. The lead is already persisted (Super Admin →
        // Inquiries), so a mail failure must never surface to the visitor.
        try {
            $owner = config('ganvo.owner_email');
            if ($owner) {
                Notification::route('mail', $owner)->notify(new ProjectInquiryReceived($inquiry));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->respondSuccess($request);
    }

    private function passesTurnstile(Request $request): bool
    {
        $secret = config('services.turnstile.secret');
        if (! $secret) {
            return true; // not configured — feature off
        }

        $token = (string) $request->input('cf-turnstile-response', '');
        if ($token === '') {
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            return (bool) $response->json('success');
        } catch (\Throwable $e) {
            // Cloudflare unreachable: fail OPEN — losing a real lead to a
            // third-party outage is worse than letting one bot through the
            // honeypot + rate limits underneath.
            report($e);

            return true;
        }
    }

    private function respondSuccess(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => \App\Models\SitePage::bulk(\App\Services\SitePageSchemas::PAGE_MARKETING_HOME)['contact_thanks']
                    ?? __('site.marketing.contact.thanks'),
            ]);
        }
        return back()->with('inquiry_status', 'ok')->withFragment('contact');
    }

    private function respondError(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], $status);
        }
        return back()->with('inquiry_error', $message)->withInput()->withFragment('contact');
    }
}
