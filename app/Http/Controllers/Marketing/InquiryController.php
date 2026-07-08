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

        // Owner mail notification temporarily disabled — the lead is still persisted
        // to the DB (Super Admin → Inquiries). Re-enable once SMTP + owner_email are set:
        // try {
        //     $owner = config('ganvo.owner_email');
        //     if ($owner) {
        //         Notification::route('mail', $owner)->notify(new ProjectInquiryReceived($inquiry));
        //     }
        // } catch (\Throwable $e) {
        //     report($e);
        // }

        return $this->respondSuccess($request);
    }

    private function respondSuccess(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('site.marketing.contact.thanks'),
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
