<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <a href="{{ url('/?preview=' . urlencode((string) config('ganvo.coming_soon.bypass_token'))) }}"
               target="_blank" rel="noopener"
               class="fi-btn fi-btn-color-gray fi-btn-size-md fi-color-gray"
               style="text-decoration: none;">
                Preview splash ↗
            </a>
            <x-filament::button type="submit">Save changes</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
