<x-filament-panels::page>
    <div style="margin-bottom: 1rem; font-size: 0.875rem; opacity: 0.75;">
        Customizing the <strong>{{ $this->themeName }}</strong> theme. Settings are saved per theme —
        switching themes keeps each theme's customizations.
    </div>

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
</x-filament-panels::page>
