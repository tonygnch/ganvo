@php
    /*
     | Discount code input — applies a manually-typed code or shows the
     | currently-applied code with a remove button. Themes include this
     | in their cart sidebar.
     |
     | Inputs (set by CartController):
     |   $applied_code    string|null  what the customer typed (and stuck)
     |   $discount        Discount|null the resolved discount (may be an
     |                                  auto-discount with no code)
     |   $discount_cents  int          amount-off in cents
     |
     | Renders an applied chip when the customer has a code set; an auto-
     | applied discount (no code) renders nothing here — it just appears
     | as a line item in the totals.
     */
@endphp

<div class="df">
    @if ($applied_code)
        {{-- Applied state: show the code as a chip + a small Remove
             form. The chip uses the resolved discount's name when the
             code is still valid; otherwise just echoes what was typed. --}}
        <form method="post" action="/cart/discount" class="df-applied">
            @csrf @method('DELETE')
            <span class="df-chip">
                <span class="df-code">{{ $applied_code }}</span>
                @if ($discount)
                    <span class="df-name">{{ $discount->name }}</span>
                @endif
            </span>
            <button type="submit" class="df-remove" aria-label="{{ __('site.cart.discount_remove') }}">×</button>
        </form>
    @else
        <form method="post" action="/cart/discount" class="df-input">
            @csrf
            <input type="text"
                   name="code"
                   placeholder="{{ __('site.cart.discount_placeholder') }}"
                   autocomplete="off"
                   inputmode="text"
                   spellcheck="false"
                   maxlength="60">
            <button type="submit">{{ __('site.cart.discount_apply') }}</button>
        </form>
    @endif
</div>

<style>
    .df { margin: .75rem 0; }
    .df-input {
        display: flex;
        gap: .375rem;
    }
    .df-input input {
        flex: 1;
        padding: .55rem .75rem;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 8px;
        font: inherit;
        font-size: .875rem;
        background: white;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .df-input input:focus { outline: none; border-color: rgba(0, 0, 0, .5); }
    .df-input button {
        padding: .55rem 1rem;
        background: rgba(0, 0, 0, .08);
        color: inherit;
        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: 8px;
        font: inherit;
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color .15s ease;
    }
    .df-input button:hover { background: rgba(0, 0, 0, .15); }

    .df-applied {
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .df-chip {
        flex: 1;
        display: inline-flex;
        align-items: baseline;
        gap: .5rem;
        padding: .5rem .75rem;
        border-radius: 999px;
        background: rgba(16, 185, 129, .12);
        border: 1px solid rgba(16, 185, 129, .35);
        color: #047857;
        font-size: .8125rem;
    }
    .df-code {
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .df-name {
        opacity: .85;
        font-size: .75rem;
    }
    .df-remove {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 50%;
        font-size: 1rem;
        line-height: 1;
        cursor: pointer;
        color: rgba(0, 0, 0, .55);
        transition: background-color .15s ease, color .15s ease;
    }
    .df-remove:hover { background: rgba(0, 0, 0, .08); color: rgba(0, 0, 0, .85); }
</style>
