<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 2rem !important; display: flex !important; flex-direction: row !important; justify-content: flex-end !important; gap: 0.75rem !important; align-items: center !important; width: 100% !important;">
            @php
                $tenant = auth()->user()->tenant;
                $previewUrl = 'http://' . $tenant->slug . '.' . config('ganvo.central_domain') . ':8000/';
            @endphp
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
               class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray"
               style="text-decoration: none;">
                Preview storefront ↗
            </a>
            <x-filament::button type="submit">Save</x-filament::button>
        </div>
    </form>

    @if (filled($store->custom_domain))
        <div style="margin-top: 2rem; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.75rem;">Custom domain setup</h3>

            @if ($store->hasVerifiedCustomDomain())
                <div style="border-radius: 0.375rem; padding: 1rem; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3);">
                    <p style="color: rgb(110,231,183); font-weight: 600;">
                        ✓ {{ $store->custom_domain }} is verified.
                    </p>
                    <p style="font-size: 0.875rem; color: rgb(167,243,208); margin-top: 0.25rem;">
                        Verified {{ $store->custom_domain_verified_at->diffForHumans() }}.
                        Visit <a href="http://{{ $store->custom_domain }}" target="_blank" style="text-decoration: underline;">http://{{ $store->custom_domain }}</a>
                    </p>
                </div>
            @else
                @php $token = $store->ensureVerificationToken(); @endphp
                <p style="font-size: 0.875rem; color: rgb(156,163,175); margin-bottom: 1rem;">
                    Follow these two steps at your DNS provider, then click <strong>Verify domain</strong> above.
                </p>

                <ol style="list-style: decimal; padding-left: 1.25rem; font-size: 0.875rem; display: flex; flex-direction: column; gap: 1rem;">
                    <li>
                        <div style="font-weight: 600;">Point traffic to Ganvo</div>
                        <div style="color: rgb(156,163,175);">Add a CNAME record:</div>
                        <pre style="margin-top: 0.25rem; background: rgba(0,0,0,0.3); border-radius: 0.25rem; padding: 0.5rem; font-size: 0.75rem; overflow-x: auto;"><code>{{ $store->custom_domain }}  CNAME  {{ config('ganvo.central_domain') }}</code></pre>
                    </li>
                    <li>
                        <div style="font-weight: 600;">Prove ownership</div>
                        <div style="color: rgb(156,163,175);">Add a TXT record:</div>
                        <pre style="margin-top: 0.25rem; background: rgba(0,0,0,0.3); border-radius: 0.25rem; padding: 0.5rem; font-size: 0.75rem; overflow-x: auto;"><code>{{ $store->custom_domain }}  TXT  {{ $token }}</code></pre>
                    </li>
                </ol>
            @endif
        </div>
    @endif
</x-filament-panels::page>
