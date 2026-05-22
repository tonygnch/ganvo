<x-filament-panels::page>
    @php
        $statusBadge = function (bool $ok, string $okLabel = 'Configured', string $missingLabel = 'Missing') {
            return $ok
                ? ['#10b981', '#064e3b', $okLabel]
                : ['#ef4444', '#7f1d1d', $missingLabel];
        };
    @endphp

    {{-- Stripe section --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 1.75rem; margin: 0 0 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin: 0 0 1.5rem;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem;">Stripe billing (platform → merchants)</h2>
                <p style="color: rgb(148,163,184); margin: 0; font-size: 0.875rem;">
                    Credentials are read from <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px; font-size: 0.8125rem;">.env</code>. Edit them on the server, then run <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px; font-size: 0.8125rem;">php artisan config:cache</code>.
                </p>
            </div>
            @php $ov = $statusBadge($stripe['has_all'], $stripe['livemode'] ? 'Live mode' : 'Test mode', 'Not configured'); @endphp
            <span style="display: inline-block; padding: 0.375rem 0.875rem; border-radius: 999px; background: {{ $ov[1] }}; color: {{ $ov[0] }}; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap;">
                {{ $ov[2] }}
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin: 0 0 1.5rem;">
            @foreach ([
                ['Publishable key', 'STRIPE_KEY', $stripe['key']],
                ['Secret key', 'STRIPE_SECRET', $stripe['secret']],
                ['Webhook secret', 'STRIPE_WEBHOOK_SECRET', $stripe['webhook_secret']],
            ] as [$label, $envVar, $value])
                @php $badge = $statusBadge((bool) $value); @endphp
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 0.75rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin: 0 0 0.5rem;">
                        <p style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(148,163,184); margin: 0;">{{ $label }}</p>
                        <span style="padding: 0.125rem 0.5rem; border-radius: 999px; background: {{ $badge[1] }}; color: {{ $badge[0] }}; font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
                            {{ $badge[2] }}
                        </span>
                    </div>
                    <code style="display: block; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.8125rem; color: rgb(203,213,225); word-break: break-all;">
                        {{ $value ?? '—' }}
                    </code>
                    <p style="margin: 0.5rem 0 0; font-size: 0.6875rem; color: rgb(100,116,139); font-family: ui-monospace, SFMono-Regular, monospace;">{{ $envVar }}</p>
                </div>
            @endforeach
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 0.75rem; padding: 1rem;">
                <p style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(148,163,184); margin: 0 0 0.5rem;">Webhook endpoint</p>
                <code style="display: block; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.8125rem; color: rgb(203,213,225); word-break: break-all;">
                    {{ $stripe['webhook_url'] }}
                </code>
                <p style="margin: 0.5rem 0 0; font-size: 0.75rem; color: rgb(148,163,184);">Register this URL in Stripe Dashboard → Developers → Webhooks.</p>
            </div>
            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 0.75rem; padding: 1rem;">
                <p style="font-size: 0.75rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: rgb(148,163,184); margin: 0 0 0.5rem;">Default currency</p>
                <code style="display: block; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.8125rem; color: rgb(203,213,225);">
                    {{ strtoupper($stripe['currency'] ?: '—') }}
                </code>
                <p style="margin: 0.5rem 0 0; font-size: 0.6875rem; color: rgb(100,116,139); font-family: ui-monospace, SFMono-Regular, monospace;">CASHIER_CURRENCY</p>
            </div>
        </div>

        @if ($ping)
            <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; border-radius: 0.75rem; background: {{ $ping['ok'] ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)' }}; border: 1px solid {{ $ping['ok'] ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)' }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 0 0 0.5rem;">
                    <p style="margin: 0; font-weight: 700; color: {{ $ping['ok'] ? 'rgb(110,231,183)' : 'rgb(252,165,165)' }};">
                        {{ $ping['ok'] ? '✓ Stripe ping succeeded' : '✕ Stripe ping failed' }}
                    </p>
                    <span style="font-size: 0.75rem; color: rgb(148,163,184); font-family: ui-monospace, monospace;">{{ $ping['at'] }}</span>
                </div>
                @if ($ping['ok'])
                    <dl style="margin: 0; display: grid; grid-template-columns: max-content 1fr; gap: 0.25rem 1rem; font-size: 0.875rem;">
                        <dt style="color: rgb(148,163,184);">Account</dt><dd style="margin: 0; font-family: ui-monospace, monospace;">{{ $ping['account_id'] }}</dd>
                        <dt style="color: rgb(148,163,184);">Name</dt><dd style="margin: 0;">{{ $ping['account_name'] }}</dd>
                        <dt style="color: rgb(148,163,184);">Country</dt><dd style="margin: 0;">{{ $ping['country'] }}</dd>
                        <dt style="color: rgb(148,163,184);">Mode</dt><dd style="margin: 0;">{{ $ping['livemode'] ? 'Live' : 'Test' }}</dd>
                    </dl>
                @else
                    <p style="margin: 0; font-size: 0.8125rem; color: rgb(252,165,165); font-family: ui-monospace, monospace;">{{ $ping['error'] }}</p>
                @endif
            </div>
        @endif

        <details style="margin-top: 1.5rem;">
            <summary style="cursor: pointer; font-size: 0.875rem; color: rgb(148,163,184);">How to set or rotate these credentials</summary>
            <ol style="margin: 0.75rem 0 0 1.25rem; padding: 0; font-size: 0.875rem; color: rgb(203,213,225); line-height: 1.7;">
                <li>Go to Stripe Dashboard → Developers → API keys, copy the publishable + secret key.</li>
                <li>Add them to <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px;">.env</code> as <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px;">STRIPE_KEY</code> and <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px;">STRIPE_SECRET</code>.</li>
                <li>Stripe Dashboard → Developers → Webhooks → "Add endpoint", paste the URL above, copy the resulting signing secret into <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px;">STRIPE_WEBHOOK_SECRET</code>.</li>
                <li>On the server, run <code style="padding: 0.125rem 0.375rem; background: rgba(255,255,255,0.06); border-radius: 4px;">php artisan config:cache</code> (or restart the app) — Laravel doesn't pick up env changes otherwise.</li>
                <li>Come back here and click "Test Stripe connection" in the header.</li>
            </ol>
        </details>
    </div>

    {{-- App + mail sidebar --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 1rem;">Application</h3>
            <dl style="margin: 0; display: grid; grid-template-columns: max-content 1fr; gap: 0.5rem 1rem; font-size: 0.875rem;">
                <dt style="color: rgb(148,163,184);">Environment</dt>
                <dd style="margin: 0;">
                    <span style="padding: 0.125rem 0.5rem; border-radius: 4px; background: {{ $app['env'] === 'production' ? 'rgba(239,68,68,0.15)' : 'rgba(99,102,241,0.15)' }}; color: {{ $app['env'] === 'production' ? 'rgb(252,165,165)' : 'rgb(165,180,252)' }}; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">{{ $app['env'] }}</span>
                </dd>
                <dt style="color: rgb(148,163,184);">URL</dt><dd style="margin: 0; font-family: ui-monospace, monospace; word-break: break-all;">{{ $app['url'] }}</dd>
                <dt style="color: rgb(148,163,184);">Debug</dt><dd style="margin: 0;">{{ $app['debug'] ? 'On' : 'Off' }}</dd>
            </dl>
        </div>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 1rem;">Mail</h3>
            <dl style="margin: 0; display: grid; grid-template-columns: max-content 1fr; gap: 0.5rem 1rem; font-size: 0.875rem;">
                <dt style="color: rgb(148,163,184);">Driver</dt><dd style="margin: 0; font-family: ui-monospace, monospace;">{{ $mail['mailer'] }}</dd>
                <dt style="color: rgb(148,163,184);">From</dt><dd style="margin: 0; font-family: ui-monospace, monospace; word-break: break-all;">{{ $mail['from_address'] ?? '—' }}</dd>
            </dl>
        </div>
    </div>
</x-filament-panels::page>
