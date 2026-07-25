<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StoreMessage;
use App\Notifications\StoreMessageReceived;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Storefront contact page — the merchant's address / phone / hours plus an
 * enquiry form.
 *
 * Spam handling mirrors the marketing inquiry form: a hidden honeypot and
 * per-IP rate limits. Turnstile is deliberately NOT used here — its keys are
 * registered per-domain and tenant storefronts each have their own host, so
 * a shared widget would fail on every store.
 *
 * The enquiry is persisted before the merchant notification is attempted, so
 * a mail-transport failure can never lose it.
 */
class ContactController extends Controller
{
    public function show(): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $contact = $store->contactPage();

        abort_unless($contact['enabled'], 404);

        $theme = $this->theme($store);
        $customer = Auth::guard('customer')->user();

        return view(
            $this->view($theme, 'contact'),
            compact('tenant', 'store', 'theme', 'contact', 'customer')
        );
    }

    public function submit(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $contact = $store->contactPage();

        abort_unless($contact['enabled'] && $contact['show_form'], 404);

        // Honeypot: `website` is hidden from humans via CSS; bots fill it.
        // Mimic success so the bot learns nothing.
        if (filled($request->input('website'))) {
            return redirect('/contact')->with('contact.sent', true);
        }

        // Throttle per IP *and* per store, so one noisy visitor cannot drown
        // a merchant's inbox and one abused store cannot spend another's quota.
        $key = 'store-message:' . $tenant->id . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key . ':burst', 3)
            || RateLimiter::tooManyAttempts($key . ':hour', 12)) {
            // Form-level, not field-level: keying this to 'message' would paint
            // the visitor's perfectly valid text red and announce it as if the
            // textarea were at fault.
            return back()->withInput()
                ->withErrors(['contact' => __('site.storefront.contact.error_throttled')]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'in:' . implode(',', StoreMessage::SUBJECTS)],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        // Only count attempts that got past validation — a typo'd email should
        // not burn the visitor's quota.
        RateLimiter::hit($key . ':burst', 60);
        RateLimiter::hit($key . ':hour', 3600);

        $data = $validator->validated();
        $customer = Auth::guard('customer')->user();

        $message = StoreMessage::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer?->id,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            'subject' => $data['subject'] ?? null,
            'message' => trim($data['message']),
            'status' => StoreMessage::STATUS_NEW,
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        // Notify the merchant. The enquiry is already saved (Store admin →
        // Messages), so a mail failure must never surface to the visitor.
        try {
            $to = $contact['email'] ?: $tenant->contact_email;
            if ($to) {
                Notification::route('mail', $to)->notify(new StoreMessageReceived($message));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect('/contact')->with('contact.sent', true);
    }

    /** Active theme slug, falling back to 'default' for unknown values. */
    private function theme($store): string
    {
        $theme = (string) ($store->theme ?? 'default');

        return ThemeRegistry::exists($theme) ? $theme : 'default';
    }

    /** Theme-specific view when it exists, otherwise the shared fallback. */
    private function view(string $theme, string $relative): string
    {
        return view()->exists("themes.{$theme}.{$relative}")
            ? "themes.{$theme}.{$relative}"
            : "storefront.{$relative}";
    }
}
