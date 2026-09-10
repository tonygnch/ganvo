{{-- The rack configurator's price book. Laid out as the merchant's own
     supplier list is, so a price rise can be typed straight down a column. --}}
<x-filament-panels::page>
    <div style="margin-bottom: 1rem; font-size: 0.875rem; opacity: 0.75;">
        {{ __('admin.configurator.text.intro') }}
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center;">
            @php
                $tenant = auth()->user()->tenant;
                $configuratorUrl = 'http://' . $tenant->slug . '.' . config('ganvo.central_domain') . ':8000/configurator';
            @endphp
            <a href="{{ $configuratorUrl }}" target="_blank" rel="noopener"
               class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray"
               style="text-decoration: none;">
                {{ __('admin.configurator.action.open') }}
            </a>
            <x-filament::button type="submit">{{ __('admin.shared.action.save') }}</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
