<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            @php
                $tenant = auth()->user()->tenant;
                $previewUrl = 'http://' . $tenant->slug . '.' . config('ganvo.central_domain') . ':8000/';
            @endphp
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
               class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray fi-link relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 rounded-lg fi-link-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:ring-white/20">
                Preview storefront ↗
            </a>
            <x-filament::button type="submit">Save</x-filament::button>
        </div>
    </form>

    @if (filled($store->custom_domain))
        <div class="mt-8 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">
            <h3 class="text-lg font-semibold mb-3">Custom domain setup</h3>

            @if ($store->hasVerifiedCustomDomain())
                <div class="rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                    <p class="text-green-800 dark:text-green-300 font-semibold">
                        ✓ {{ $store->custom_domain }} is verified.
                    </p>
                    <p class="text-sm text-green-700 dark:text-green-400 mt-1">
                        Verified {{ $store->custom_domain_verified_at->diffForHumans() }}.
                        Visit <a href="http://{{ $store->custom_domain }}" target="_blank" class="underline">http://{{ $store->custom_domain }}</a>
                    </p>
                </div>
            @else
                @php $token = $store->ensureVerificationToken(); @endphp
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Follow these two steps at your DNS provider, then click <strong>Verify domain</strong> above.
                </p>

                <ol class="space-y-4 list-decimal pl-5 text-sm">
                    <li>
                        <div class="font-semibold">Point traffic to Ganvo</div>
                        <div class="text-gray-600 dark:text-gray-400">Add a CNAME record:</div>
                        <pre class="mt-1 bg-gray-100 dark:bg-gray-800 rounded p-2 text-xs overflow-x-auto"><code>{{ $store->custom_domain }}  CNAME  {{ config('ganvo.central_domain') }}</code></pre>
                    </li>
                    <li>
                        <div class="font-semibold">Prove ownership</div>
                        <div class="text-gray-600 dark:text-gray-400">Add a TXT record:</div>
                        <pre class="mt-1 bg-gray-100 dark:bg-gray-800 rounded p-2 text-xs overflow-x-auto"><code>{{ $store->custom_domain }}  TXT  {{ $token }}</code></pre>
                    </li>
                </ol>
            @endif
        </div>
    @endif
</x-filament-panels::page>
