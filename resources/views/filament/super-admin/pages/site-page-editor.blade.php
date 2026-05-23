<x-filament-panels::page>
    @php
        // Build the live-preview URL based on which page we're editing.
        // Coming-soon needs the bypass token query param so the operator
        // can see the splash even when coming-soon mode is off; the
        // marketing home is publicly accessible.
        $pageSlug = $this::pageSlug();
        $previewUrl = match ($pageSlug) {
            \App\Services\SitePageSchemas::PAGE_COMING_SOON =>
                url('/?preview=' . urlencode((string) config('ganvo.coming_soon.bypass_token'))),
            \App\Services\SitePageSchemas::PAGE_MARKETING_HOME => url('/'),
            default => url('/'),
        };
        $previewLabel = $pageSlug === \App\Services\SitePageSchemas::PAGE_COMING_SOON
            ? 'Preview splash ↗'
            : 'Preview page ↗';
    @endphp

    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <a href="{{ $previewUrl }}"
               target="_blank" rel="noopener"
               class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray"
               style="text-decoration: none;">
                {{ $previewLabel }}
            </a>
            <x-filament::button type="submit">Save changes</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
