<x-filament-panels::page>
    <div style="margin-bottom: 1rem; font-size: 0.875rem; opacity: 0.75;">
        {!! __('admin.theme.text.customizing', ['theme' => '<strong>' . e($this->themeName) . '</strong>']) !!}
        {{ __('admin.theme.text.saved_per_theme') }}
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
                {{ __('admin.shared.action.preview_storefront') }}
            </a>
            <x-filament::button type="submit">{{ __('admin.shared.action.save') }}</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
