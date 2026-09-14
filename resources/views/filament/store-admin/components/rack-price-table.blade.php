{{--
    One price table of the rack configurator: a row per size down the side, a
    column per depth across the top, one price per cell — the merchant's own
    supplier list. A blank cell is a size that is not sold. The inputs are bound
    straight to the page's data.prices; see RackConfigurator::mount().

    The table fills its section and shares the width out evenly, so it fits
    without a scrollbar. It scrolls sideways only on a screen too narrow for
    the columns at their smallest — a phone with six depths, say.

    @var string $corner  what the rows and columns are
    @var array  $rows    [key, label, sub]
    @var array  $cols    [key, label, sub]
    @var string $path    data.prices.{kind}.{type}
    @var string $symbol  the store's currency symbol
--}}
<style>
    .rack-price-table { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0 0.375rem; }
    .rack-price-table th, .rack-price-table td { padding: 0 0.25rem; }
    .rack-price-table th:first-child { padding-inline-start: 0; }
    .rack-price-table td:last-child, .rack-price-table th:last-child { padding-inline-end: 0; }
    .rack-price-table .rack-price-corner { width: 8.5rem; }
    .rack-price-table td { min-width: 6.5rem; }
    /* the spinner arrows only take room from the price: typing is how a price list is filled in */
    .rack-price-table input[type=number] { -moz-appearance: textfield; appearance: textfield; }
    .rack-price-table input[type=number]::-webkit-inner-spin-button,
    .rack-price-table input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>
{{-- the padding keeps the inputs' outer border and focus ring inside the clipping box --}}
<div style="overflow-x: auto; overflow-y: hidden; padding: 3px;">
    <table class="rack-price-table" style="min-width: calc(8.5rem + {{ count($cols) }} * 6.5rem);">
        <thead>
            <tr>
                <th scope="col" class="rack-price-corner" style="text-align: start; vertical-align: bottom; font-size: 0.75rem; font-weight: 500; opacity: 0.65;">
                    {{ $corner }}
                </th>
                @foreach ($cols as $col)
                    <th scope="col" style="text-align: start; vertical-align: bottom; font-size: 0.875rem; font-weight: 600; white-space: nowrap;">
                        {{ $col['label'] }}
                        @if (filled($col['sub'] ?? null))
                            <div style="font-size: 0.75rem; font-weight: 400; opacity: 0.6;">{{ $col['sub'] }}</div>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th scope="row" style="text-align: start; font-size: 0.875rem; font-weight: 600; white-space: nowrap;">
                        {{ $row['label'] }}
                        @if (filled($row['sub'] ?? null))
                            <div style="font-size: 0.75rem; font-weight: 400; opacity: 0.6;">{{ $row['sub'] }}</div>
                        @endif
                    </th>
                    @foreach ($cols as $col)
                        @php($cell = $row['key'].'x'.$col['key'])
                        <td>
                            <x-filament::input.wrapper :prefix="$symbol">
                                <x-filament::input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    inputmode="decimal"
                                    placeholder="—"
                                    wire:model="{{ $path }}.{{ $cell }}"
                                    aria-label="{{ $row['label'] }} × {{ $col['label'] }}"
                                />
                            </x-filament::input.wrapper>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
